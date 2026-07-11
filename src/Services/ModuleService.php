<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\Event;
use Lartrix\Models\Module;
use Lartrix\Modules\Manifest\ModuleManifest;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/** 负责本地模块发现、同步、启停、安装和卸载。 */
class ModuleService extends BaseService
{
    /**
     * 获取所有模块列表
     */
    public function getModules(): array
    {
        // 同步模块状态到数据库
        $this->syncModules();

        return Module::orderBy('name')->get()->toArray();
    }

    /**
     * 启用模块
     */
    public function enable(string $name): bool
    {
        /** @var Module|null $module */
        $module = Module::where('name', $name)->first();

        if (!$module) {
            return false;
        }

        // 启用 nwidart/laravel-modules 模块
        $laravelModule = ModuleFacade::find($name);
        if ($laravelModule) {
            $laravelModule->enable();
        }

        // 更新数据库状态
        $module->enable();

        // 触发事件
        Event::dispatch('lartrix.module.enabled', [$module]);

        return true;
    }

    /**
     * 禁用模块
     */
    public function disable(string $name): bool
    {
        /** @var Module|null $module */
        $module = Module::where('name', $name)->first();

        if (!$module) {
            return false;
        }

        // 禁用 nwidart/laravel-modules 模块
        $laravelModule = ModuleFacade::find($name);
        if ($laravelModule) {
            $laravelModule->disable();
        }

        // 更新数据库状态
        $module->disable();

        // 触发事件
        Event::dispatch('lartrix.module.disabled', [$module]);

        return true;
    }

    /**
     * 同步模块状态到数据库
     */
    public function syncModules(): void
    {
        $laravelModules = ModuleFacade::all();

        foreach ($laravelModules as $name => $laravelModule) {
            // 优先读取 Trix module.json；旧 nwidart module.json 会在 loader 中归一化。
            $moduleJson = $this->getModuleConfig($laravelModule);

            Module::updateOrCreate(
                ['name' => $name],
                [
                    'title' => $moduleJson['title'] ?? $moduleJson['name'] ?? $name,
                    'description' => $moduleJson['description'] ?? '',
                    'version' => $moduleJson['version'] ?? '',
                    'author' => $moduleJson['author'] ?? '',
                    'website' => $moduleJson['website'] ?? $moduleJson['url'] ?? '',
                    'logo' => $this->moduleLogoUrl($name, $laravelModule, (string) ($moduleJson['logo'] ?? '')),
                    'enabled' => $this->resolveModuleEnabledState($name, $laravelModule, $moduleJson),
                    'config' => $moduleJson,
                ]
            );
        }

        // 删除不存在的模块记录
        $existingNames = array_keys($laravelModules);
        Module::whereNotIn('name', $existingNames)->delete();
    }

    /**
     * 检查模块是否存在
     */
    public function exists(string $name): bool
    {
        return Module::where('name', $name)->exists();
    }

    /**
     * 检查模块是否启用
     */
    public function isEnabled(string $name): bool
    {
        /** @var Module|null $module */
        $module = Module::where('name', $name)->first();
        return $module && $module->isEnabled();
    }

    /**
     * 安装模块：迁移 + 填充 + 注册菜单权限
     */
    public function install(string $name): bool
    {
        /** @var Module|null $module */
        $module = Module::where('name', $name)->first();

        // 数据库无记录时，从 nwidart 模块系统发现
        if (!$module) {
            $laravelModule = \Nwidart\Modules\Facades\Module::find($name);
            if (!$laravelModule) { return false; }
            $moduleJson = $this->getModuleConfig($laravelModule);
            $module = Module::create([
                'name' => $name,
                'enabled' => false,
                'title' => $moduleJson['title'] ?? $name,
                'description' => $moduleJson['description'] ?? '',
                'version' => $moduleJson['version'] ?? '1.0.0',
                'author' => $moduleJson['author'] ?? '',
                'website' => $moduleJson['website'] ?? $moduleJson['url'] ?? '',
                'logo' => $this->moduleLogoUrl($name, $laravelModule, (string) ($moduleJson['logo'] ?? '')),
                'config' => $moduleJson,
            ]);
        }
        if ($module->enabled) { return true; }

        // 读取 module.json 注册菜单和权限
        $laravelModule = \Nwidart\Modules\Facades\Module::find($name);
        if ($laravelModule) {
            $moduleJson = $this->getModuleConfig($laravelModule);
            $this->registerMenus($moduleJson, $name);
            $this->registerPermissions($moduleJson, $name);

            // 先启用（Artisan 子进程依赖启用状态）
            $laravelModule->enable();
            $module->enable();
            $module->save();

            // 使用独立进程执行迁移和填充（避免类名冲突）
            \Artisan::call('module:migrate', ['module' => $name]);
            \Artisan::call('module:seed', ['module' => $name]);
        } else {
            $module->enable();
            $module->save();
        }

        Event::dispatch('lartrix.module.installed', [$module]);
        return true;
    }

    /**
     * 卸载模块：删除菜单权限 + 回滚迁移
     */
    public function uninstall(string $name): bool
    {
        /** @var Module|null $module */
        $module = Module::where('name', $name)->first();
        if (!$module) { return false; }
        if (!$module->enabled) { return true; }

        // 删除模块菜单
        $menuModel = config('lartrix.models.menu', \Lartrix\Models\Menu::class);
        $menuModel::where('module', $name)->delete();

        // 删除模块权限
        $permissionModel = config('lartrix.models.permission', \Lartrix\Models\Permission::class);
        $permissionModel::where('module', $name)->delete();

        // 回滚迁移并禁用
        $laravelModule = \Nwidart\Modules\Facades\Module::find($name);
        if ($laravelModule) {
            \Artisan::call('module:migrate-rollback', ['module' => $name]);
            $laravelModule->disable();
        }

        $module->disable();
        Event::dispatch('lartrix.module.uninstalled', [$module]);
        return true;
    }

        /** 注册当前模块贡献的数据。 */
    protected function registerMenus(array $moduleJson, string $moduleName): void
    {
        $menus = $moduleJson['menus'] ?? [];
        if (empty($menus)) { return; }
        $menuModel = config('lartrix.models.menu', \Lartrix\Models\Menu::class);
        $guard = config('lartrix.guard', 'admin');
        foreach ($menus as $menu) {
            if (!isset($menu['name']) && isset($menu['key'])) {
                $menu['name'] = $menu['key'];
            }
            if (isset($menu['permission']) && !isset($menu['permissions'])) {
                $menu['permissions'] = [$menu['permission']];
            }
            unset($menu['key'], $menu['parent'], $menu['permission']);

            $menu['guard_name'] = $guard;
            $menu['module'] = $moduleName;
            $exists = $menuModel::where('name', $menu['name'])->where('guard_name', $guard)->first();
            if (!$exists) { $menuModel::create($menu); }
        }
    }

        /** 注册当前模块贡献的数据。 */
    protected function registerPermissions(array $moduleJson, string $moduleName): void
    {
        $permissions = $moduleJson['permissions'] ?? [];
        if (empty($permissions)) { return; }
        $permissionModel = config('lartrix.models.permission', \Lartrix\Models\Permission::class);
        $guard = config('lartrix.guard', 'admin');
        foreach ($permissions as $perm) {
            unset($perm['group']);

            $perm['guard_name'] = $guard;
            $perm['module'] = $moduleName;
            $exists = $permissionModel::where('name', $perm['name'])->where('guard_name', $guard)->first();
            if (!$exists) { $permissionModel::create($perm); }
        }
    }

    /**
     * 获取当前业务对象所需的数据。
     * @param object $laravelModule
     * @return array<string, mixed>
     */
    protected function getModuleConfig(object $laravelModule): array
    {
        $legacyConfig = $laravelModule->json()->getAttributes();
        $modulePath = method_exists($laravelModule, 'getPath') ? $laravelModule->getPath() : null;

        if (!is_string($modulePath) || $modulePath === '') {
            return $legacyConfig;
        }

        $manifest = (new ModuleManifestLoader())->loadFromPath($modulePath);

        if (!$manifest) {
            return $legacyConfig;
        }

        // 保留旧 nwidart 字段，同时把 Trix manifest 放入 trix_manifest 供市场和中间件使用。
        return $this->manifestToModuleConfig($manifest, $legacyConfig);
    }

    /**
     * 执行 manifestToModuleConfig 方法对应的具体职责。
     * @param array<string, mixed> $legacyConfig
     * @return array<string, mixed>
     */
    protected function manifestToModuleConfig(ModuleManifest $manifest, array $legacyConfig = []): array
    {
        $manifestData = $manifest->toArray();

        return array_merge($legacyConfig, $manifestData, [
            'title' => $manifest->name() ?: ($legacyConfig['title'] ?? $legacyConfig['name'] ?? ''),
            'description' => $manifestData['description'] ?? $legacyConfig['description'] ?? '',
            'version' => $manifest->version() ?: ($legacyConfig['version'] ?? ''),
            'menus' => $manifest->menus(),
            'permissions' => $manifest->permissions(),
            'trix_manifest' => $manifestData,
        ]);
    }

        /** 执行 moduleLogoUrl 方法对应的具体职责。 */
    protected function moduleLogoUrl(string $name, object $laravelModule, string $logo): string
    {
        $logo = trim($logo);
        if ($logo === '' || filter_var($logo, FILTER_VALIDATE_URL) || str_starts_with($logo, '/')) {
            return $logo;
        }

        $modulePath = method_exists($laravelModule, 'getPath') ? $laravelModule->getPath() : null;
        if (!is_string($modulePath) || $modulePath === '' || !is_file($modulePath . DIRECTORY_SEPARATOR . $logo)) {
            return '';
        }

        $prefix = trim((string) config('lartrix.api_prefix', 'api/admin'), '/');

        return '/' . $prefix . '/modules/' . rawurlencode($name) . '/logo';
    }

    /**
     * 解析并返回当前流程所需的目标值。
     * @param object $laravelModule
     * @param array<string, mixed> $moduleJson
     */
    protected function resolveModuleEnabledState(string $name, object $laravelModule, array $moduleJson): bool
    {
        if ($laravelModule->isEnabled()) {
            return true;
        }

        // 标准模块名可能从 "Module Market" 迁移为 "ModuleMarket"/"modulemarket"，这里承接旧状态键。
        if (!$this->hasEnabledLegacyModuleStatus($name, $laravelModule, $moduleJson)) {
            return false;
        }

        if (method_exists($laravelModule, 'enable')) {
            $laravelModule->enable();
        }

        return true;
    }

    /**
     * 判断当前业务条件是否成立。
     * @param object $laravelModule
     * @param array<string, mixed> $moduleJson
     */
    protected function hasEnabledLegacyModuleStatus(string $name, object $laravelModule, array $moduleJson): bool
    {
        $statuses = $this->moduleStatuses();
        if ($statuses === []) {
            return false;
        }

        $currentKeys = $this->currentModuleStatusKeys($name, $laravelModule);
        foreach ($currentKeys as $key) {
            if (array_key_exists($key, $statuses) && $statuses[$key] === false) {
                return false;
            }
        }

        foreach ($this->moduleStatusAliases($name, $laravelModule, $moduleJson) as $alias) {
            if (!in_array($alias, $currentKeys, true) && ($statuses[$alias] ?? false) === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * 执行 moduleStatuses 方法对应的具体职责。
     * @return array<string, bool>
     */
    protected function moduleStatuses(): array
    {
        $statusFile = config('modules.activators.file.statuses-file', base_path('modules_statuses.json'));
        if (!is_string($statusFile) || $statusFile === '' || !is_file($statusFile)) {
            return [];
        }

        $statuses = json_decode((string) file_get_contents($statusFile), true);
        if (!is_array($statuses)) {
            return [];
        }

        return array_filter($statuses, static fn ($enabled) => is_bool($enabled));
    }

    /**
     * 执行 currentModuleStatusKeys 方法对应的具体职责。
     * @param object $laravelModule
     * @return array<int, string>
     */
    protected function currentModuleStatusKeys(string $name, object $laravelModule): array
    {
        $keys = [$name];
        if (method_exists($laravelModule, 'getName')) {
            $keys[] = (string) $laravelModule->getName();
        }

        $currentKeys = [];
        foreach ($keys as $key) {
            $key = trim($key);
            if ($key === '') {
                continue;
            }

            $currentKeys[] = $key;
            $currentKeys[] = strtolower($key);
        }

        return array_values(array_unique($currentKeys));
    }

    /**
     * 执行 moduleStatusAliases 方法对应的具体职责。
     * @param object $laravelModule
     * @param array<string, mixed> $moduleJson
     * @return array<int, string>
     */
    protected function moduleStatusAliases(string $name, object $laravelModule, array $moduleJson): array
    {
        $aliases = $this->currentModuleStatusKeys($name, $laravelModule);

        foreach (['name', 'alias', 'title', 'id'] as $key) {
            if (isset($moduleJson[$key]) && is_string($moduleJson[$key])) {
                $aliases[] = $moduleJson[$key];
            }
        }

        if (isset($moduleJson['trix_manifest']) && is_array($moduleJson['trix_manifest'])) {
            foreach (['name', 'alias', 'title', 'id'] as $key) {
                if (isset($moduleJson['trix_manifest'][$key]) && is_string($moduleJson['trix_manifest'][$key])) {
                    $aliases[] = $moduleJson['trix_manifest'][$key];
                }
            }
        }

        return $this->normalizeStatusAliases($aliases);
    }

    /**
     * 将输入值归一化为内部标准格式。
     * @param array<int, string> $aliases
     * @return array<int, string>
     */
    protected function normalizeStatusAliases(array $aliases): array
    {
        $normalized = [];
        foreach ($aliases as $alias) {
            $alias = trim($alias);
            if ($alias === '') {
                continue;
            }

            $normalized[] = $alias;
            $normalized[] = strtolower($alias);

            $spaced = trim((string) preg_replace('/(?<!^)[A-Z]/', ' $0', $alias));
            if ($spaced !== '') {
                $normalized[] = $spaced;
                $normalized[] = strtolower($spaced);
            }
        }

        return array_values(array_unique($normalized));
    }
}

<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Artisan;
use Lartrix\Models\Module;
use Lartrix\Modules\Manifest\ModuleManifest;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use Nwidart\Modules\Facades\Module as ModuleFacade;

/** 负责本地模块发现、同步、启停、安装和卸载。 */
class ModuleService
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
            // Nwidart 根字段负责模块运行，trix 节点只负责生态元数据。
            $moduleJson = $this->getModuleConfig($laravelModule);

            Module::updateOrCreate(
                ['name' => $name],
                [
                    'title' => $moduleJson['title'] ?? $moduleJson['name'] ?? $name,
                    'registry_id' => $moduleJson['registry_id'] ?? null,
                    'description' => $moduleJson['description'] ?? '',
                    'version' => $moduleJson['version'] ?? '',
                    'author' => $moduleJson['author'] ?? '',
                    'website' => $moduleJson['website'] ?? $moduleJson['url'] ?? '',
                    'logo' => $this->moduleLogoUrl($name, $laravelModule, (string) ($moduleJson['logo'] ?? '')),
                    'enabled' => $laravelModule->isEnabled(),
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
                'registry_id' => $moduleJson['registry_id'] ?? null,
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
            $migrated = false;
            try {
                if (Artisan::call('module:migrate', ['module' => $name]) !== 0) {
                    throw new \RuntimeException("模块 [{$name}] 的迁移失败");
                }
                $migrated = true;
                if (Artisan::call('module:seed', ['module' => $name]) !== 0) {
                    throw new \RuntimeException("模块 [{$name}] 的数据填充失败");
                }
            } catch (\Throwable $e) {
                if ($migrated) {
                    try {
                        if (Artisan::call('module:migrate-rollback', ['module' => $name]) !== 0) {
                            throw new \RuntimeException("模块 [{$name}] 安装失败后的迁移回滚也失败，请人工检查数据库");
                        }
                    } catch (\Throwable $rollbackError) {
                        report($rollbackError);
                        $config = is_array($module->config) ? $module->config : [];
                        $config['lifecycle_error'] = 'install_rollback_failed';
                        $module->config = $config;
                        $module->save();
                    }
                }
                $this->removeModuleContributions($name);
                $laravelModule->disable();
                $module->disable();
                report($e);
                return false;
            }
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

        // 先回滚迁移；失败时保留模块注册信息和启用状态，避免半卸载。
        $laravelModule = \Nwidart\Modules\Facades\Module::find($name);
        if ($laravelModule) {
            try {
                if (Artisan::call('module:migrate-rollback', ['module' => $name]) !== 0) {
                    return false;
                }
            } catch (\Throwable $e) {
                report($e);
                return false;
            }
            $laravelModule->disable();
        }

        $this->removeModuleContributions($name);
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
        $nwidart = $laravelModule->json()->getAttributes();
        $modulePath = method_exists($laravelModule, 'getPath') ? $laravelModule->getPath() : null;

        if (!is_string($modulePath) || $modulePath === '') {
            return ['name' => $nwidart['name'] ?? '', 'title' => $nwidart['name'] ?? '', 'description' => $nwidart['description'] ?? ''];
        }

        $manifest = (new ModuleManifestLoader())->loadFromPath($modulePath);

        if (!$manifest) {
            return ['name' => $nwidart['name'] ?? '', 'title' => $nwidart['name'] ?? '', 'description' => $nwidart['description'] ?? ''];
        }

        return $this->manifestToModuleConfig($manifest);
    }

    /**
     * 执行 manifestToModuleConfig 方法对应的具体职责。
     * @return array<string, mixed>
     */
    protected function manifestToModuleConfig(ModuleManifest $manifest): array
    {
        $manifestData = $manifest->toArray();

        return [
            'registry_id' => $manifest->id(),
            'name' => $manifest->name(),
            'title' => $manifest->name(),
            'description' => $manifestData['description'] ?? '',
            'version' => $manifest->version(),
            'type' => $manifest->type(),
            'logo' => $manifest->logo(),
            'thumbnail' => $manifest->thumbnail(),
            'author' => $manifest->author(),
            'author_url' => $manifest->authorUrl(),
            'adapter' => $manifest->adapter(),
            'menus' => $manifest->menus(),
            'permissions' => $manifest->permissions(),
            'schemas' => $manifest->schemas(),
            'security' => $manifest->security(),
        ];
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

    /** 删除模块注册的菜单、权限及角色权限关联。 */
    protected function removeModuleContributions(string $name): void
    {
        $menuModel = config('lartrix.models.menu', \Lartrix\Models\Menu::class);
        $permissionModel = config('lartrix.models.permission', \Lartrix\Models\Permission::class);
        $permissionIds = $permissionModel::where('module', $name)->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            \Illuminate\Support\Facades\DB::table('role_has_permissions')
                ->whereIn('permission_id', $permissionIds)->delete();
            \Illuminate\Support\Facades\DB::table('model_has_permissions')
                ->whereIn('permission_id', $permissionIds)->delete();
        }

        $menuModel::where('module', $name)->delete();
        $permissionModel::where('module', $name)->delete();
    }

}

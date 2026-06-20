<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\Event;
use Lartrix\Models\Module;
use Nwidart\Modules\Facades\Module as ModuleFacade;

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
            $moduleJson = $laravelModule->json();

            Module::updateOrCreate(
                ['name' => $name],
                [
                    'title' => $moduleJson->get('title') ?: $moduleJson->get('name', $name),
                    'description' => $moduleJson->get('description', ''),
                    'version' => $moduleJson->get('version', ''),
                    'author' => $moduleJson->get('author', ''),
                    'website' => $moduleJson->get('website') ?: $moduleJson->get('url', ''),
                    'logo' => $moduleJson->get('logo', ''),
                    'enabled' => $laravelModule->isEnabled(),
                    'config' => $moduleJson->getAttributes(),
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
        if (!$module) { return false; }
        if ($module->enabled) { return true; }

        // 读取 module.json 注册菜单和权限
        $laravelModule = \Nwidart\Modules\Facades\Module::find($name);
        if ($laravelModule) {
            $moduleJson = $laravelModule->json()->getAttributes();
            $this->registerMenus($moduleJson, $name);
            $this->registerPermissions($moduleJson, $name);

            // 执行模块迁移
            $laravelModule->enable();
            \Artisan::call('module:migrate', ['module' => $name]);
            \Artisan::call('module:seed', ['module' => $name]);
        }

        // 更新数据库状态
        $module->enable();
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

    protected function registerMenus(array $moduleJson, string $moduleName): void
    {
        $menus = $moduleJson['menus'] ?? [];
        if (empty($menus)) { return; }
        $menuModel = config('lartrix.models.menu', \Lartrix\Models\Menu::class);
        $guard = config('lartrix.guard', 'admin');
        foreach ($menus as $menu) {
            $menu['guard_name'] = $guard;
            $menu['module'] = $moduleName;
            $exists = $menuModel::where('name', $menu['name'])->where('guard_name', $guard)->first();
            if (!$exists) { $menuModel::create($menu); }
        }
    }

    protected function registerPermissions(array $moduleJson, string $moduleName): void
    {
        $permissions = $moduleJson['permissions'] ?? [];
        if (empty($permissions)) { return; }
        $permissionModel = config('lartrix.models.permission', \Lartrix\Models\Permission::class);
        $guard = config('lartrix.guard', 'admin');
        foreach ($permissions as $perm) {
            $perm['guard_name'] = $guard;
            $perm['module'] = $moduleName;
            $exists = $permissionModel::where('name', $perm['name'])->where('guard_name', $guard)->first();
            if (!$exists) { $permissionModel::create($perm); }
        }
    }
}

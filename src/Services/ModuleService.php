<?php

namespace Lartrix\Services;

use Illuminate\Support\Facades\Event;
use Lartrix\Models\Module;
use Nwidart\Modules\Facades\Module as ModuleFacade;

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
                    'title' => $moduleJson->get('title', $name),
                    'description' => $moduleJson->get('description'),
                    'version' => $moduleJson->get('version'),
                    'author' => $moduleJson->get('author'),
                    'website' => $moduleJson->get('website'),
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
        $module = Module::where('name', $name)->first();
        return $module && $module->isEnabled();
    }
}

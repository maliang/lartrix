<?php

namespace Lartrix;

use Illuminate\Support\ServiceProvider;
use Lartrix\Services\AuthService;
use Lartrix\Services\DataDictService;
use Lartrix\Services\ModuleService;
use Lartrix\Services\PermissionService;
use Lartrix\Services\RealtimeService;
use Lartrix\Services\TranslationService;

/** 注册并启动 Lartrix 服务、路由、资源及模块贡献。 */
class LartrixServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 合并配置
        $this->mergeConfigFrom(__DIR__ . '/../config/lartrix.php', 'lartrix');

        // 注册单例服务
        $this->app->singleton(AuthService::class);
        $this->app->singleton(DataDictService::class);
        $this->app->singleton(ModuleService::class);
        $this->app->singleton(PermissionService::class);
        $this->app->singleton(RealtimeService::class);
        $this->app->singleton(TranslationService::class);
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 合并已启用模块贡献的导航栏自定义项与实时消息行为
        $this->mergeModuleContributions();

        // 加载语言包
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'lartrix');

        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/lartrix.php' => config_path('lartrix.php'),
        ], 'lartrix-config');

        // 发布语言文件
        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/lartrix'),
        ], 'lartrix-translations');

        // 发布前端资源到 public/admin 目录
        $this->publishes([
            __DIR__ . '/../resources/admin/' => public_path('admin'),
        ], 'lartrix-assets');

        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 加载迁移文件
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');

        // 注册命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                Commands\InstallCommand::class,
                Commands\PublishAssetsCommand::class,
                Commands\UninstallCommand::class,
                Commands\MakeBackendCommand::class,
Commands\BackendInstallCommand::class,
                Commands\ModuleMakeCommand::class,
                Commands\RemoveBackendCommand::class,
                Commands\ModuleInstallCommand::class,
                Commands\ModuleUpdateCommand::class,
                Commands\ModuleUninstallCommand::class,
                Commands\ProjectMakeCommand::class,
                Commands\ProjectInstallCommand::class,
                Commands\ProjectPublishCommand::class,
            ]);
        }
    }

    /**
     * 将已启用模块声明的 header_custom_items / realtime_behaviors 合并到全局配置
     *
     * 模块在其 config/config.php 中声明这两个键，启用后自动合并：
     *   'header_custom_items' => [ ['icon' => '...', 'tooltip' => '...', 'badge' => [...], 'click' => 'route', 'click_target' => '/...'] ],
     *   'realtime_behaviors'  => [ 'mobile.recharge.pending' => ['notify' => false, 'actions' => [['type' => 'sound', 'src' => '/voice/chongzhi.mp3', 'times' => 3]]] ],
     *
     * - custom_items：模块项追加在全局配置项之后（全局 custom_items_position 仍决定整体位置）
     * - realtime_behaviors：按 type 合并，同 type 时全局 config/lartrix.php 优先于模块声明
     */
    protected function mergeModuleContributions(): void
    {
        try {
            $moduleNames = \Lartrix\Models\Module::where('enabled', true)->pluck('name')->all();
        } catch (\Throwable $e) {
            // 数据库未就绪（安装阶段等）时静默跳过
            return;
        }

        if (empty($moduleNames)) {
            return;
        }

        $headerItems = [];
        $behaviors = [];

        foreach ($moduleNames as $name) {
            $config = $this->readModuleConfig($name);
            if ($config === null) {
                continue;
            }

            if (!empty($config['header_custom_items']) && is_array($config['header_custom_items'])) {
                $headerItems = array_merge($headerItems, array_values($config['header_custom_items']));
            }
            if (!empty($config['realtime_behaviors']) && is_array($config['realtime_behaviors'])) {
                $behaviors = array_merge($behaviors, $config['realtime_behaviors']);
            }
        }

        if (!empty($headerItems)) {
            $existing = config('lartrix.header.custom_items', []);
            if (!is_array($existing)) {
                $existing = [];
            }
            config(['lartrix.header.custom_items' => array_merge($existing, $headerItems)]);
        }

        if (!empty($behaviors)) {
            $existing = config('lartrix.realtime.behaviors', []);
            if (!is_array($existing)) {
                $existing = [];
            }
            // 全局配置优先：模块声明在前，全局覆盖同名 type
            config(['lartrix.realtime.behaviors' => array_merge($behaviors, $existing)]);
        }
    }

    /**
     * 读取指定模块的 config/config.php
     */
    protected function readModuleConfig(string $name): ?array
    {
        try {
            $module = \Nwidart\Modules\Facades\Module::find($name);
            if (!$module) {
                return null;
            }

            $path = $module->getPath() . '/config/config.php';
            if (!is_file($path)) {
                return null;
            }

            $config = require $path;
            return is_array($config) ? $config : null;
        } catch (\Throwable $e) {
            return null;
        }
    }
}

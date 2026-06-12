<?php

namespace Lartrix;

use Illuminate\Support\ServiceProvider;
use Lartrix\Services\AuthService;
use Lartrix\Services\DataDictService;
use Lartrix\Services\ModuleService;
use Lartrix\Services\PermissionService;
use Lartrix\Services\RealtimeService;

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
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/lartrix.php' => config_path('lartrix.php'),
        ], 'lartrix-config');

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
                Commands\RemoveBackendCommand::class,
            ]);
        }
    }
}

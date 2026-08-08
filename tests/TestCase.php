<?php

namespace Lartrix\Tests;

use Laravel\Sanctum\SanctumServiceProvider;
use Lartrix\LartrixServiceProvider;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Permission;
use Lartrix\Models\Role;
use Nwidart\Modules\LaravelModulesServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Spatie\Permission\PermissionServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            SanctumServiceProvider::class,
            PermissionServiceProvider::class,
            LaravelModulesServiceProvider::class,
            LartrixServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // 使用 SQLite 内存数据库进行测试
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // 配置 Sanctum
        $app['config']->set('sanctum.stateful', []);

        // Lartrix API 前缀（测试统一使用 /api/lartrix）
        $app['config']->set('lartrix.api_prefix', 'api/lartrix');

        // 配置 admin guard（路由中间件使用 auth:admin）
        $app['config']->set('auth.guards.admin', [
            'driver' => 'sanctum',
            'provider' => 'admin_users',
        ]);
        $app['config']->set('auth.providers.admin_users', [
            'driver' => 'eloquent',
            'model' => AdminUser::class,
        ]);

        // spatie/laravel-permission 使用 Lartrix 扩展模型
        $app['config']->set('permission.models.permission', Permission::class);
        $app['config']->set('permission.models.role', Role::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        // 运行迁移：核心表（stubs/migrations）+ spatie + sanctum
        $this->loadMigrationsFrom($this->prepareMigrationDirectory());

        // 运行 Lartrix 自身辅助表迁移（dict / notification 等）
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }

    /**
     * 把核心表（stubs/migrations）、spatie、sanctum 迁移复制到临时目录并返回路径。
     * 说明：核心表迁移由 lartrix:install 在安装时从 stubs 发布到宿主项目，
     * 测试环境需要手动加载这些迁移才能建立完整的数据表结构。
     */
    protected function prepareMigrationDirectory(): string
    {
        static $dir = null;
        if ($dir !== null) {
            return $dir;
        }

        $dir = sys_get_temp_dir() . '/lartrix_test_migrations_' . getmypid() . '_' . substr(md5(uniqid('', true)), 0, 8);
        @mkdir($dir, 0777, true);

        $sources = [
            // sanctum
            __DIR__ . '/../vendor/laravel/sanctum/database/migrations/2019_12_14_000001_create_personal_access_tokens_table.php' => '2019_12_14_000001_create_personal_access_tokens_table.php',
            // spatie/laravel-permission
            __DIR__ . '/../vendor/spatie/laravel-permission/database/migrations/create_permission_tables.php.stub' => '2020_01_01_000001_create_permission_tables.php',
            // lartrix 核心表（stubs/migrations）
            __DIR__ . '/../stubs/migrations/create_admin_users_table.php.stub' => '2021_01_01_000001_create_admin_users_table.php',
            __DIR__ . '/../stubs/migrations/add_fields_to_permission_tables.php.stub' => '2021_01_01_000002_add_fields_to_permission_tables.php',
            __DIR__ . '/../stubs/migrations/create_admin_menus_table.php.stub' => '2021_01_01_000003_create_admin_menus_table.php',
            __DIR__ . '/../stubs/migrations/add_badge_to_admin_menus_table.php.stub' => '2021_01_01_000004_add_badge_to_admin_menus_table.php',
            __DIR__ . '/../stubs/migrations/create_admin_settings_table.php.stub' => '2021_01_01_000005_create_admin_settings_table.php',
            __DIR__ . '/../stubs/migrations/create_modules_table.php.stub' => '2021_01_01_000006_create_modules_table.php',
        ];

        foreach ($sources as $from => $to) {
            if (file_exists($from)) {
                copy($from, $dir . '/' . $to);
            }
        }

        return $dir;
    }
}

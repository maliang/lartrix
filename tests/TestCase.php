<?php

namespace Lartrix\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Lartrix\LartrixServiceProvider;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
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
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // 运行迁移
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

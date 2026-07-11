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
        // 浣跨敤 SQLite 鍐呭瓨鏁版嵁搴撹繘琛屾祴璇?
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // 閰嶇疆 Sanctum
        $app['config']->set('sanctum.stateful', []);
    }

    protected function setUp(): void
    {
        parent::setUp();
        
        // 杩愯杩佺Щ
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
    }
}

<?php

namespace Lartrix\Tests\Unit\Commands;

use Lartrix\Tests\TestCase;
use Lartrix\Commands\BackendInstallCommand;
use Illuminate\Support\Facades\File;

class BackendInstallCommandTest extends TestCase
{
    /** @test */
    public function it_fails_when_module_does_not_exist(): void
    {
        $this->artisan('lartrix:backend-install', ['name' => 'GhostModule'])
            ->expectsOutputToContain('不存在')
            ->assertExitCode(1);
    }

    /** @test */
    public function it_registers_the_command(): void
    {
        $provider = file_get_contents(__DIR__ . '/../../../src/LartrixServiceProvider.php');

        $this->assertStringContainsString(
            'Commands\\BackendInstallCommand::class',
            $provider
        );
    }

    /** @test */
    public function auth_guard_configuration_is_idempotent(): void
    {
        // 备份测试环境原 auth.php
        $authPath = config_path('auth.php');
        $original = File::get($authPath);

        try {
            // 第一次调用：写入 guard/provider
            $command = $this->makeAuthConfiguringCommand();
            $changed = $this->invokeConfigureAuth($command, 'merchant', 'Merchant');
            $this->assertTrue($changed);

            $content = File::get($authPath);
            $this->assertStringContainsString("'merchant' => [", $content);
            $this->assertStringContainsString("'provider' => 'merchants',", $content);
            $this->assertStringContainsString("'merchants' => [", $content);
            $this->assertStringContainsString('Modules\\Merchant\\Models\\Merchant::class', $content);

            // 第二次调用：不重复写入
            $command2 = $this->makeAuthConfiguringCommand();
            $changed2 = $this->invokeConfigureAuth($command2, 'merchant', 'Merchant');
            $this->assertFalse($changed2);
        } finally {
            File::put($authPath, $original);
        }
    }

    protected function makeAuthConfiguringCommand(): BackendInstallCommand
    {
        return new BackendInstallCommand();
    }

    protected function invokeConfigureAuth(BackendInstallCommand $command, string $guard, string $moduleName): bool
    {
        $method = new \ReflectionMethod($command, 'configureBackendAuth');
        $method->setAccessible(true);
        return (bool) $method->invoke($command, $guard, $moduleName);
    }
}

<?php

namespace Lartrix\Tests\Unit\Commands;

use Lartrix\Tests\TestCase;
use Lartrix\Commands\InstallCommand;
use Illuminate\Support\Facades\File;

class InstallCommandTest extends TestCase
{
    protected function makeRealCommand(): InstallCommand
    {
        $command = new InstallCommand();
        $input = new \Symfony\Component\Console\Input\ArrayInput([]);
        $input->bind($command->getDefinition());
        $command->setInput($input);
        $command->setOutput(new \Illuminate\Console\OutputStyle(
            $input,
            new \Symfony\Component\Console\Output\BufferedOutput()
        ));
        return $command;
    }

    protected function invokePublishDependencies(InstallCommand $command): void
    {
        // 快照 config 与 migrations 目录，测试后完整恢复（publishDependencies 会发布三方配置/迁移）
        $configSnapshot = $this->snapshotDir(config_path());
        $migrationSnapshot = $this->snapshotDir(database_path('migrations'));

        try {
            $method = new \ReflectionMethod($command, 'publishDependencies');
            $method->setAccessible(true);
            $method->invoke($command);
        } finally {
            // lartrix.php 由各测试用例自己管理（存在/缺失两种场景）
            $this->restoreDir(config_path(), $configSnapshot, ['lartrix.php']);
            $this->restoreDir(database_path('migrations'), $migrationSnapshot);
        }
    }

    protected function snapshotDir(string $dir): array
    {
        $files = [];
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $files[basename($file)] = File::get($file);
        }
        return $files;
    }

    protected function restoreDir(string $dir, array $snapshot, array $exclude = []): void
    {
        // 删除本次新增的文件（排除豁免项）
        foreach (glob($dir . '/*.php') ?: [] as $file) {
            $name = basename($file);
            if (!isset($snapshot[$name]) && !in_array($name, $exclude, true)) {
                File::delete($file);
            }
        }
        // 恢复被覆盖/删除的文件内容（排除豁免项）
        foreach ($snapshot as $name => $content) {
            if (in_array($name, $exclude, true)) {
                continue;
            }
            File::put($dir . '/' . $name, $content);
        }
    }

    /** @test */
    public function it_does_not_overwrite_existing_lartrix_config(): void
    {
        $configPath = config_path('lartrix.php');
        $exists = File::exists($configPath);
        $original = $exists ? File::get($configPath) : null;

        File::put($configPath, "<?php\nreturn ['custom' => true];\n");

        try {
            $this->invokePublishDependencies($this->makeRealCommand());

            // 已有配置保持原样，未被覆盖
            $this->assertSame("<?php\nreturn ['custom' => true];\n", File::get($configPath));
        } finally {
            if ($exists) {
                File::put($configPath, $original);
            } else {
                File::delete($configPath);
            }
        }
    }

    /** @test */
    public function it_publishes_lartrix_config_when_missing(): void
    {
        $configPath = config_path('lartrix.php');
        $exists = File::exists($configPath);
        $original = $exists ? File::get($configPath) : null;

        if ($exists) {
            File::delete($configPath);
        }

        try {
            $this->invokePublishDependencies($this->makeRealCommand());

            // 配置被发布（来自包内 config）
            $this->assertTrue(File::exists($configPath));
            $content = File::get($configPath);
            $this->assertStringContainsString("'api_prefix'", $content);
        } finally {
            if ($exists && $original !== null) {
                File::put($configPath, $original);
            } else {
                File::delete($configPath);
            }
        }
    }
}

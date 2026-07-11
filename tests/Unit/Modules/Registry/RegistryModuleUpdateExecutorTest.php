<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryModuleUpdateExecutor;
use PHPUnit\Framework\TestCase;

class RegistryModuleUpdateExecutorTest extends TestCase
{
    public function testReplacesModuleWhenTargetVersionIsNewerAndConfirmed(): void
    {
        $paths = $this->makeUpdateFixture('1.0.0', '1.1.0');

        $result = (new RegistryModuleUpdateExecutor('php', 'laravel'))->execute(
            $paths['target'],
            $paths['source'],
            'module.json',
            'official.cms',
            '1.1.0',
            $paths['backup'],
            true
        );

        self::assertTrue($result['updated']);
        self::assertSame('update_available', $result['action']);
        self::assertFileExists($paths['backup'] . DIRECTORY_SEPARATOR . 'old.txt');
        self::assertFileExists($paths['target'] . DIRECTORY_SEPARATOR . 'new.txt');
    }

    public function testSkipsWhenTargetVersionMatchesCurrentVersion(): void
    {
        $paths = $this->makeUpdateFixture('1.1.0', '1.1.0');

        $result = (new RegistryModuleUpdateExecutor('php', 'laravel'))->execute(
            $paths['target'],
            $paths['source'],
            'module.json',
            'official.cms',
            '1.1.0',
            $paths['backup'],
            true
        );

        self::assertFalse($result['updated']);
        self::assertSame('already_current', $result['action']);
        self::assertFileExists($paths['target'] . DIRECTORY_SEPARATOR . 'old.txt');
        self::assertDirectoryDoesNotExist($paths['backup']);
    }

    public function testPreviewsUpdateWithoutReplacingDirectories(): void
    {
        $paths = $this->makeUpdateFixture('1.0.0', '1.1.0');

        $result = (new RegistryModuleUpdateExecutor('php', 'laravel'))->preview(
            $paths['target'],
            $paths['source'],
            'module.json',
            'official.cms',
            '1.1.0'
        );

        self::assertTrue($result['allowed']);
        self::assertSame('update_available', $result['action']);
        self::assertSame('1.0.0', $result['current_version']);
        self::assertSame('1.1.0', $result['target_version']);
        self::assertFileExists($paths['target'] . DIRECTORY_SEPARATOR . 'old.txt');
        self::assertFileExists($paths['source'] . DIRECTORY_SEPARATOR . 'new.txt');
        self::assertDirectoryDoesNotExist($paths['backup']);
    }

    public function testPreviewsDowngradeWhenExplicitlyAllowed(): void
    {
        $paths = $this->makeUpdateFixture('1.2.0', '1.1.0');

        $result = (new RegistryModuleUpdateExecutor('php', 'laravel'))->preview(
            $paths['target'],
            $paths['source'],
            'module.json',
            'official.cms',
            '1.1.0',
            true
        );

        self::assertTrue($result['allowed']);
        self::assertSame('downgrade_allowed', $result['action']);
    }

    /**
     * @return array{source: string, target: string, backup: string}
     */
    private function makeUpdateFixture(string $currentVersion, string $targetVersion): array
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-update-' . uniqid('', true);
        $source = $root . DIRECTORY_SEPARATOR . 'OfficialCmsNext';
        $target = $root . DIRECTORY_SEPARATOR . 'OfficialCms';
        $backup = $root . DIRECTORY_SEPARATOR . 'OfficialCmsBackup';

        mkdir($source, 0775, true);
        mkdir($target, 0775, true);
        file_put_contents($source . DIRECTORY_SEPARATOR . 'new.txt', 'new');
        file_put_contents($target . DIRECTORY_SEPARATOR . 'old.txt', 'old');
        file_put_contents($source . DIRECTORY_SEPARATOR . 'module.json', $this->manifest($targetVersion));
        file_put_contents($target . DIRECTORY_SEPARATOR . 'module.json', $this->manifest($currentVersion));

        return ['source' => $source, 'target' => $target, 'backup' => $backup];
    }

    private function manifest(string $version): string
    {
        return json_encode([
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => $version,
            'type' => 'contract',
            'adapter' => [
                'language' => 'php',
                'framework' => 'laravel',
                'status' => 'stable',
                'package_type' => 'composer',
            ],
            'security' => [
                'writes_files' => true,
                'runs_commands' => false,
                'external_network' => false,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}

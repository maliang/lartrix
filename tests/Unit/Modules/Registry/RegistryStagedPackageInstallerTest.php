<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryStagedPackageInstaller;
use PHPUnit\Framework\TestCase;

class RegistryStagedPackageInstallerTest extends TestCase
{
    public function testCopiesVerifiedStageDirectoryToMissingTargetDirectory(): void
    {
        $stage = $this->makeStage([
            'official.cms/module.json' => '{"schema_version":"trix.module.v1"}',
            'official.cms/composer.json' => '{}',
        ]);
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-install-target-' . uniqid('', true) . DIRECTORY_SEPARATOR . 'OfficialCms';

        $result = (new RegistryStagedPackageInstaller())->install(
            $stage,
            'official.cms/module.json',
            $target
        );

        self::assertTrue($result['installed']);
        self::assertSame($target, $result['path']);
        self::assertFileExists($target . DIRECTORY_SEPARATOR . 'module.json');
        self::assertFileExists($target . DIRECTORY_SEPARATOR . 'composer.json');
    }

    public function testRejectsExistingTargetDirectory(): void
    {
        $stage = $this->makeStage([
            'official.cms/module.json' => '{}',
        ]);
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-install-target-' . uniqid('', true) . DIRECTORY_SEPARATOR . 'OfficialCms';
        mkdir($target, 0775, true);

        $result = (new RegistryStagedPackageInstaller())->install(
            $stage,
            'official.cms/module.json',
            $target
        );

        self::assertFalse($result['installed']);
        self::assertSame('target_exists', $result['reason']);
    }

    public function testRejectsMissingManifestInStage(): void
    {
        $stage = $this->makeStage([
            'official.cms/README.md' => '# CMS',
        ]);
        $target = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-install-target-' . uniqid('', true) . DIRECTORY_SEPARATOR . 'OfficialCms';

        $result = (new RegistryStagedPackageInstaller())->install(
            $stage,
            'official.cms/module.json',
            $target
        );

        self::assertFalse($result['installed']);
        self::assertSame('manifest_missing', $result['reason']);
    }

    /**
     * @param array<string, string> $files
     */
    private function makeStage(array $files): string
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-install-stage-' . uniqid('', true);

        foreach ($files as $path => $content) {
            $fullPath = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path);
            $dir = dirname($fullPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            file_put_contents($fullPath, $content);
        }

        return $root;
    }
}

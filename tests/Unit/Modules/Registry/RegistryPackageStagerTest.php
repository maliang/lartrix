<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryPackageStager;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class RegistryPackageStagerTest extends TestCase
{
    public function testExtractsPackageIntoIsolatedStagingDirectory(): void
    {
        $package = $this->makeZip([
            'official.cms/module.json' => '{"schema_version":"trix.module.v1"}',
            'official.cms/composer.json' => '{}',
        ]);
        $stagingRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-stage-test-' . uniqid('', true);

        $result = (new RegistryPackageStager($stagingRoot))->stage($package, 'official.cms', '1.0.0');

        self::assertTrue($result['staged']);
        self::assertDirectoryExists($result['path']);
        self::assertFileExists($result['path'] . DIRECTORY_SEPARATOR . 'official.cms' . DIRECTORY_SEPARATOR . 'module.json');
        self::assertStringStartsWith($stagingRoot, $result['path']);
        self::assertSame('official.cms/module.json', $result['manifest']);
    }

    public function testRejectsPackageWithUnsafePathsBeforeExtracting(): void
    {
        $package = $this->makeZip([
            '../evil.php' => '<?php echo "bad";',
            'official.cms/module.json' => '{}',
        ]);
        $stagingRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-stage-test-' . uniqid('', true);

        $result = (new RegistryPackageStager($stagingRoot))->stage($package, 'official.cms', '1.0.0');

        self::assertFalse($result['staged']);
        self::assertSame('unsafe_path', $result['reason']);
    }

    public function testRejectsPackageWithoutManifestBeforeExtracting(): void
    {
        $package = $this->makeZip([
            'official.cms/README.md' => '# CMS',
        ]);
        $stagingRoot = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-stage-test-' . uniqid('', true);

        $result = (new RegistryPackageStager($stagingRoot))->stage($package, 'official.cms', '1.0.0');

        self::assertFalse($result['staged']);
        self::assertSame('manifest_missing', $result['reason']);
    }

    /**
     * @param array<string, string> $entries
     */
    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lartrix-stage-package-') . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }
}

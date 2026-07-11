<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryPackagePreflightInspector;
use PHPUnit\Framework\TestCase;
use ZipArchive;

class RegistryPackagePreflightInspectorTest extends TestCase
{
    public function testAcceptsZipContainingModuleManifest(): void
    {
        $path = $this->makeZip([
            'official.cms/module.json' => '{"schema_version":"trix.module.v1"}',
            'official.cms/composer.json' => '{}',
        ]);

        $result = (new RegistryPackagePreflightInspector())->inspect($path);

        self::assertTrue($result['ok']);
        self::assertSame('official.cms/module.json', $result['manifest']);
        self::assertSame(2, $result['file_count']);
    }

    public function testRejectsPathTraversalEntries(): void
    {
        $path = $this->makeZip([
            '../evil.php' => '<?php echo "bad";',
            'official.cms/module.json' => '{}',
        ]);

        $result = (new RegistryPackagePreflightInspector())->inspect($path);

        self::assertFalse($result['ok']);
        self::assertSame('unsafe_path', $result['reason']);
        self::assertStringContainsString('../evil.php', $result['message']);
    }

    public function testRejectsPackageWithoutManifest(): void
    {
        $path = $this->makeZip([
            'official.cms/README.md' => '# CMS',
        ]);

        $result = (new RegistryPackagePreflightInspector())->inspect($path);

        self::assertFalse($result['ok']);
        self::assertSame('manifest_missing', $result['reason']);
    }

    /**
     * @param array<string, string> $entries
     */
    private function makeZip(array $entries): string
    {
        $path = tempnam(sys_get_temp_dir(), 'lartrix-preflight-') . '.zip';
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }

        $zip->close();

        return $path;
    }
}

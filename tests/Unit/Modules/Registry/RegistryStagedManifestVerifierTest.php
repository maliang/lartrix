<?php

namespace Lartrix\Tests\Unit\Modules\Registry;

use Lartrix\Modules\Registry\RegistryStagedManifestVerifier;
use PHPUnit\Framework\TestCase;

class RegistryStagedManifestVerifierTest extends TestCase
{
    public function testAcceptsManifestMatchingExpectedModuleVersionAndAdapter(): void
    {
        $root = $this->makeStage([
            'official.cms/module.json' => $this->manifest('official.cms', '1.0.0', 'laravel', 'stable'),
        ]);

        $result = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify(
            $root,
            'official.cms/module.json',
            'official.cms',
            '1.0.0'
        );

        self::assertTrue($result['ok']);
        self::assertSame('official.cms', $result['manifest_id']);
        self::assertSame('stable', $result['adapter_status']);
        self::assertSame(['writes_files' => true], $result['security']);
    }

    public function testRejectsMismatchedModuleId(): void
    {
        $root = $this->makeStage([
            'official.cms/module.json' => $this->manifest('evil.cms', '1.0.0', 'laravel', 'stable'),
        ]);

        $result = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify(
            $root,
            'official.cms/module.json',
            'official.cms',
            '1.0.0'
        );

        self::assertFalse($result['ok']);
        self::assertSame('module_id_mismatch', $result['reason']);
    }

    public function testRejectsAdapterThatIsNotInstallable(): void
    {
        $root = $this->makeStage([
            'official.cms/module.json' => $this->manifest('official.cms', '1.0.0', 'laravel', 'planned'),
        ]);

        $result = (new RegistryStagedManifestVerifier('php', 'laravel'))->verify(
            $root,
            'official.cms/module.json',
            'official.cms',
            '1.0.0'
        );

        self::assertFalse($result['ok']);
        self::assertSame('manifest_adapter_invalid', $result['reason']);
    }

    /**
     * @param array<string, string> $files
     */
    private function makeStage(array $files): string
    {
        $root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-verify-stage-' . uniqid('', true);

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

    private function manifest(string $id, string $version, string $framework, string $status): string
    {
        return json_encode([
            'schema_version' => 'trix.module.v1',
            'id' => $id,
            'name' => 'CMS',
            'version' => $version,
            'type' => 'contract',
            'adapter' => [
                'language' => 'php',
                'framework' => $framework,
                'status' => $status,
            ],
            'security' => [
                'writes_files' => true,
            ],
        ], JSON_THROW_ON_ERROR);
    }
}

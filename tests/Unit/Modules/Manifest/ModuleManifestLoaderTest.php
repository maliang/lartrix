<?php

namespace Lartrix\Tests\Unit\Modules\Manifest;

use InvalidArgumentException;
use Lartrix\Modules\Manifest\ModuleManifestLoader;
use PHPUnit\Framework\TestCase;

class ModuleManifestLoaderTest extends TestCase
{
    private string $tempPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-manifest-' . uniqid('', true);
        mkdir($this->tempPath, 0777, true);
    }

    protected function tearDown(): void
    {
        @unlink($this->tempPath . DIRECTORY_SEPARATOR . 'module.json');
        @rmdir($this->tempPath);
        parent::tearDown();
    }

    /** @test */
    public function it_loads_the_trix_node_from_a_valid_nwidart_manifest(): void
    {
        $this->writeJson($this->moduleManifest());
        $manifest = (new ModuleManifestLoader())->loadFromPath($this->tempPath);

        $this->assertNotNull($manifest);
        $this->assertSame('official.cms', $manifest->id());
        $this->assertSame($this->moduleManifest()['trix'], $manifest->toArray());
    }

    /** @test */
    public function it_returns_null_when_module_json_or_trix_node_is_missing(): void
    {
        $loader = new ModuleManifestLoader();
        $this->assertNull($loader->loadFromPath($this->tempPath));

        $manifest = $this->moduleManifest();
        unset($manifest['trix']);
        $this->writeJson($manifest);

        $this->assertNull($loader->loadFromPath($this->tempPath));
    }

    /** @test */
    public function it_does_not_normalize_a_legacy_flat_trix_manifest(): void
    {
        $this->writeJson([
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'native',
            'adapter' => ['language' => 'php', 'framework' => 'laravel'],
        ]);

        $this->assertNull((new ModuleManifestLoader())->loadFromPath($this->tempPath));
    }

    /** @test */
    public function it_rejects_invalid_nwidart_root_fields(): void
    {
        $manifest = $this->moduleManifest();
        unset($manifest['providers']);
        $this->writeJson($manifest);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('providers');
        (new ModuleManifestLoader())->loadFromPath($this->tempPath);
    }

    /** @test */
    public function it_rejects_invalid_trix_fields(): void
    {
        $manifest = $this->moduleManifest();
        $manifest['trix']['id'] = '';
        $this->writeJson($manifest);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('trix.id');
        (new ModuleManifestLoader())->loadFromPath($this->tempPath);
    }

    /** @test */
    public function it_rejects_invalid_json_manifest(): void
    {
        file_put_contents($this->tempPath . DIRECTORY_SEPARATOR . 'module.json', '{bad json');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON manifest');
        (new ModuleManifestLoader())->loadFromPath($this->tempPath);
    }

    /** @return array<string, mixed> */
    private function moduleManifest(): array
    {
        return [
            'name' => 'Cms',
            'alias' => 'cms',
            'description' => 'Content management',
            'keywords' => ['cms'],
            'priority' => 0,
            'providers' => ['Modules\\Cms\\Providers\\CmsServiceProvider'],
            'files' => [],
            'trix' => [
                'schema_version' => 'trix.module.v1',
                'id' => 'official.cms',
                'name' => 'CMS',
                'version' => '1.0.0',
                'type' => 'native',
                'adapter' => [
                    'language' => 'php',
                    'framework' => 'laravel',
                    'package_type' => 'nwidart',
                ],
            ],
        ];
    }

    /** @param array<string, mixed> $data */
    private function writeJson(array $data): void
    {
        file_put_contents(
            $this->tempPath . DIRECTORY_SEPARATOR . 'module.json',
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)
        );
    }
}

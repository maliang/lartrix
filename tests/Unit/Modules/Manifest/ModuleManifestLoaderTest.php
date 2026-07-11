<?php

namespace Lartrix\Tests\Unit\Modules\Manifest;

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
        $this->deleteDirectory($this->tempPath);

        parent::tearDown();
    }

    /** @test */
    public function it_loads_module_json_manifest_when_present(): void
    {
        $this->writeJson('module.json', [
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'contract',
            'adapter' => [
                'language' => 'php',
                'framework' => 'laravel',
                'status' => 'stable',
            ],
        ]);

        $manifest = (new ModuleManifestLoader())->loadFromPath($this->tempPath);

        $this->assertNotNull($manifest);
        $this->assertSame('official.cms', $manifest->id());
        $this->assertSame('stable', $manifest->adapterStatus());
    }

    /** @test */
    public function it_normalizes_legacy_module_json(): void
    {
        $this->writeJson('module.json', [
            'name' => 'Blog',
            'title' => 'Blog Module',
            'description' => 'Post management',
            'version' => '0.1.0',
            'menus' => [
                ['name' => 'blog.posts', 'title' => 'Posts', 'path' => '/blog/posts'],
            ],
            'permissions' => [
                ['name' => 'blog.posts.view', 'title' => 'View posts'],
            ],
        ]);

        $manifest = (new ModuleManifestLoader())->loadFromPath($this->tempPath);

        $this->assertNotNull($manifest);
        $this->assertSame('legacy.blog', $manifest->id());
        $this->assertSame('Blog Module', $manifest->name());
        $this->assertSame('0.1.0', $manifest->version());
        $this->assertSame('native', $manifest->type());
        $this->assertSame('compatible', $manifest->adapterStatus());
        $this->assertSame('laravel', $manifest->adapterFramework());
        $this->assertSame('Posts', $manifest->menus()[0]['title']);
        $this->assertSame('blog.posts.view', $manifest->permissions()[0]['name']);
    }

    /** @test */
    public function it_returns_null_when_no_manifest_file_exists(): void
    {
        $manifest = (new ModuleManifestLoader())->loadFromPath($this->tempPath);

        $this->assertNull($manifest);
    }

    /** @test */
    public function it_rejects_invalid_json_manifest(): void
    {
        file_put_contents($this->tempPath . DIRECTORY_SEPARATOR . 'module.json', '{bad json');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JSON manifest');

        (new ModuleManifestLoader())->loadFromPath($this->tempPath);
    }

    /** @test */
    public function it_loads_shared_example_manifests(): void
    {
        $examplesRoot = dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'examples' . DIRECTORY_SEPARATOR . 'modules';
        $loader = new ModuleManifestLoader();

        $dashboard = $loader->loadFromPath($examplesRoot . DIRECTORY_SEPARATOR . 'pure-schema-dashboard');
        $cms = $loader->loadFromPath($examplesRoot . DIRECTORY_SEPARATOR . 'contract-cms');
        $audit = $loader->loadFromPath($examplesRoot . DIRECTORY_SEPARATOR . 'native-laravel-audit');

        $this->assertNotNull($dashboard);
        $this->assertSame('pure_schema', $dashboard->type());
        $this->assertSame('stable', $dashboard->adapterStatus());

        $this->assertNotNull($cms);
        $this->assertSame('official.cms', $cms->id());
        $this->assertSame('stable', $cms->adapterStatus());
        $this->assertSame('laravel', $cms->adapterFramework());

        $this->assertNotNull($audit);
        $this->assertSame('official.laravel-audit', $audit->id());
        $this->assertSame('stable', $audit->adapterStatus());
        $this->assertSame('laravel', $audit->adapterFramework());
    }

    /**
     * @param array<string, mixed> $data
     */
    private function writeJson(string $filename, array $data): void
    {
        file_put_contents(
            $this->tempPath . DIRECTORY_SEPARATOR . $filename,
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $items = scandir($path);

        if ($items === false) {
            return;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $fullPath = $path . DIRECTORY_SEPARATOR . $item;

            if (is_dir($fullPath)) {
                $this->deleteDirectory($fullPath);
                continue;
            }

            unlink($fullPath);
        }

        rmdir($path);
    }
}

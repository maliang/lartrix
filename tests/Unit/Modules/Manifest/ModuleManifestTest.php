<?php

namespace Lartrix\Tests\Unit\Modules\Manifest;

use Lartrix\Modules\Manifest\ModuleManifestLoader;
use PHPUnit\Framework\TestCase;

class ModuleManifestTest extends TestCase
{
    /** @test */
    public function it_exposes_only_the_validated_trix_manifest(): void
    {
        $path = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lartrix-manifest-value-' . uniqid('', true);
        mkdir($path, 0777, true);

        try {
            $trix = [
                'schema_version' => 'trix.module.v1',
                'id' => 'official.dashboard',
                'name' => 'Dashboard',
                'version' => '1.0.0',
                'type' => 'pure_schema',
                'adapter' => [
                    'language' => 'php',
                    'framework' => 'laravel',
                    'package_type' => 'nwidart',
                ],
            ];
            file_put_contents($path . DIRECTORY_SEPARATOR . 'module.json', json_encode([
                'name' => 'Dashboard',
                'alias' => 'dashboard',
                'description' => 'Dashboard module',
                'keywords' => [],
                'priority' => 0,
                'providers' => ['Modules\\Dashboard\\Providers\\DashboardServiceProvider'],
                'files' => [],
                'trix' => $trix,
            ], JSON_THROW_ON_ERROR));

            $manifest = (new ModuleManifestLoader())->loadFromPath($path);

            $this->assertNotNull($manifest);
            $this->assertSame('official.dashboard', $manifest->id());
            $this->assertSame('laravel', $manifest->adapterFramework());
            $this->assertSame($trix, $manifest->toArray());
            $this->assertArrayNotHasKey('providers', $manifest->toArray());
            $this->assertFalse(method_exists($manifest, 'adapterStatus'));
        } finally {
            @unlink($path . DIRECTORY_SEPARATOR . 'module.json');
            @rmdir($path);
        }
    }
}

<?php

namespace Lartrix\Tests\Unit\Modules\Manifest;

use Lartrix\Modules\Manifest\ModuleManifest;
use PHPUnit\Framework\TestCase;

class ModuleManifestTest extends TestCase
{
    /** @test */
    public function it_can_create_manifest_from_minimal_array(): void
    {
        $manifest = ModuleManifest::fromArray($this->manifest());

        $this->assertSame('official.dashboard', $manifest->id());
        $this->assertSame('Dashboard', $manifest->name());
        $this->assertSame('1.0.0', $manifest->version());
        $this->assertSame('pure_schema', $manifest->type());
        $this->assertSame('assets/logo.svg', $manifest->logo());
        $this->assertSame('assets/cover.png', $manifest->thumbnail());
        $this->assertSame('Trix Official', $manifest->author());
        $this->assertSame('https://www.trixmore.lav', $manifest->authorUrl());
        $this->assertSame('php', $manifest->adapterLanguage());
        $this->assertSame('laravel', $manifest->adapterFramework());
        $this->assertSame('stable', $manifest->adapterStatus());
    }

    /** @test */
    public function it_exposes_adapter_menus_permissions_schemas_and_security(): void
    {
        $manifest = ModuleManifest::fromArray(array_merge($this->manifest(), [
            'menus' => [
                ['key' => 'cms.posts', 'title' => '鏂囩珷绠＄悊', 'path' => '/cms/posts'],
            ],
            'permissions' => [
                ['name' => 'cms.posts.view', 'title' => '鏌ョ湅鏂囩珷'],
            ],
            'schemas' => [
                ['key' => 'posts.index', 'title' => '鏂囩珷鍒楄〃', 'path' => 'schemas/posts.index.json'],
            ],
            'security' => [
                'writes_files' => true,
                'runs_commands' => false,
            ],
        ]));

        $this->assertSame('laravel', $manifest->adapterFramework());
        $this->assertSame('鏂囩珷绠＄悊', $manifest->menus()[0]['title']);
        $this->assertSame('cms.posts.view', $manifest->permissions()[0]['name']);
        $this->assertSame('posts.index', $manifest->schemas()[0]['key']);
        $this->assertTrue($manifest->security()['writes_files']);
        $this->assertFalse($manifest->security()['runs_commands']);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        return [
            'schema_version' => 'trix.module.v1',
            'id' => 'official.dashboard',
            'name' => 'Dashboard',
            'version' => '1.0.0',
            'type' => 'pure_schema',
            'logo' => 'assets/logo.svg',
            'thumbnail' => 'assets/cover.png',
            'author' => 'Trix Official',
            'author_url' => 'https://www.trixmore.lav',
            'adapter' => [
                'language' => 'php',
                'language_version' => '>=8.2',
                'framework' => 'laravel',
                'framework_version' => '>=12.0',
                'status' => 'stable',
            ],
        ];
    }
}

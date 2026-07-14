<?php

namespace Lartrix\Tests\Unit\Modules\Manifest;

use Lartrix\Modules\Manifest\ModuleManifestValidator;
use PHPUnit\Framework\TestCase;

class ModuleManifestValidatorTest extends TestCase
{
    /** @test */
    public function it_accepts_valid_nwidart_and_trix_manifests(): void
    {
        $this->assertSame([], ModuleManifestValidator::validateNwidart($this->nwidartManifest()));
        $this->assertSame([], ModuleManifestValidator::validate($this->trixManifest()));
    }

    /** @test */
    public function it_strictly_validates_nwidart_required_fields(): void
    {
        $errors = ModuleManifestValidator::validateNwidart([
            'name' => '',
            'alias' => 12,
            'description' => [],
            'keywords' => 'cms',
            'priority' => '0',
            'providers' => [false],
            'files' => [12],
        ]);

        foreach (['name', 'alias', 'description', 'keywords', 'priority', 'providers.0', 'files.0'] as $key) {
            $this->assertArrayHasKey($key, $errors);
        }
    }

    /** @test */
    public function it_strictly_validates_trix_required_fields_and_lists(): void
    {
        $errors = ModuleManifestValidator::validate([
            'schema_version' => 'trix.module.v2',
            'id' => '',
            'name' => 123,
            'version' => [],
            'type' => 'unknown',
            'adapter' => [
                'language' => '',
                'framework' => 123,
                'package_type' => [],
                'language_version' => false,
                'framework_version' => 12,
            ],
            'menus' => 'invalid',
            'permissions' => [['name' => 'cms.view']],
            'schemas' => [['key' => 'cms.index', 'title' => 'CMS']],
        ]);

        foreach ([
            'schema_version', 'id', 'name', 'version', 'type', 'adapter.language',
            'adapter.framework', 'adapter.package_type', 'adapter.language_version',
            'adapter.framework_version', 'menus', 'permissions.0.title', 'schemas.0.path',
        ] as $key) {
            $this->assertArrayHasKey($key, $errors);
        }
    }

    /** @test */
    public function it_ignores_market_owned_adapter_status(): void
    {
        $manifest = $this->trixManifest();
        $manifest['adapter']['status'] = ['not', 'a', 'package', 'field'];

        $this->assertSame([], ModuleManifestValidator::validate($manifest));
        $this->assertSame([], ModuleManifestValidator::validateForAdapter($manifest, 'php', 'laravel'));
    }

    /** @test */
    public function it_validates_only_the_requested_technical_adapter_identity(): void
    {
        $errors = ModuleManifestValidator::validateForAdapter($this->trixManifest(), 'php', 'thinkphp');

        $this->assertArrayHasKey('adapter.framework', $errors);
        $this->assertArrayNotHasKey('adapter.status', $errors);
    }

    /** @return array<string, mixed> */
    private function nwidartManifest(): array
    {
        return [
            'name' => 'Cms',
            'alias' => 'cms',
            'description' => 'Content management',
            'keywords' => ['cms'],
            'priority' => 0,
            'providers' => ['Modules\\Cms\\Providers\\CmsServiceProvider'],
            'files' => [],
        ];
    }

    /** @return array<string, mixed> */
    private function trixManifest(): array
    {
        return [
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'native',
            'adapter' => [
                'language' => 'php',
                'language_version' => '>=8.2',
                'framework' => 'laravel',
                'framework_version' => '>=12.0',
                'package_type' => 'nwidart',
            ],
        ];
    }
}

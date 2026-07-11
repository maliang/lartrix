<?php

namespace Lartrix\Tests\Unit\Modules\Manifest;

use Lartrix\Modules\Manifest\ModuleManifestValidator;
use PHPUnit\Framework\TestCase;

class ModuleManifestValidatorTest extends TestCase
{
    /** @test */
    public function it_accepts_a_valid_manifest(): void
    {
        $this->assertSame([], ModuleManifestValidator::validate($this->manifest()));
    }

    /** @test */
    public function it_rejects_missing_required_fields(): void
    {
        $errors = ModuleManifestValidator::validate([]);

        $this->assertArrayHasKey('schema_version', $errors);
        $this->assertArrayHasKey('id', $errors);
        $this->assertArrayHasKey('name', $errors);
        $this->assertArrayHasKey('version', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('adapter', $errors);
    }

    /** @test */
    public function it_rejects_invalid_schema_version_type_and_adapter_status(): void
    {
        $errors = ModuleManifestValidator::validate([
            'schema_version' => 'trix.module.v2',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'unknown',
            'adapter' => [
                'language' => 'php',
                'framework' => 'laravel',
                'status' => 'done',
            ],
        ]);

        $this->assertArrayHasKey('schema_version', $errors);
        $this->assertArrayHasKey('type', $errors);
        $this->assertArrayHasKey('adapter.status', $errors);
    }

    /** @test */
    public function it_rejects_install_when_adapter_is_not_installable_or_missing(): void
    {
        $planned = ModuleManifestValidator::validateForAdapter(array_replace_recursive($this->manifest(), [
            'adapter' => ['status' => 'planned'],
        ]), 'php', 'laravel');

        $missing = ModuleManifestValidator::validateForAdapter($this->manifest(), 'php', 'thinkphp');

        $this->assertArrayHasKey('adapter.status', $planned);
        $this->assertArrayHasKey('adapter.framework', $missing);
    }

    /** @test */
    public function it_rejects_incomplete_menu_permission_and_schema_entries(): void
    {
        $errors = ModuleManifestValidator::validate(array_merge($this->manifest(), [
            'menus' => [
                ['key' => 'cms.posts', 'title' => '鏂囩珷绠＄悊'],
            ],
            'permissions' => [
                ['name' => 'cms.posts.view'],
            ],
            'schemas' => [
                ['key' => 'posts.index', 'title' => '鏂囩珷鍒楄〃'],
            ],
        ]));

        $this->assertArrayHasKey('menus.0.path', $errors);
        $this->assertArrayHasKey('permissions.0.title', $errors);
        $this->assertArrayHasKey('schemas.0.path', $errors);
    }

    /** @test */
    public function it_rejects_non_string_display_metadata(): void
    {
        $errors = ModuleManifestValidator::validate(array_merge($this->manifest(), [
            'logo' => ['assets/logo.svg'],
            'thumbnail' => 123,
            'author' => false,
            'author_url' => ['https://www.trixmore.lav'],
        ]));

        $this->assertArrayHasKey('logo', $errors);
        $this->assertArrayHasKey('thumbnail', $errors);
        $this->assertArrayHasKey('author', $errors);
        $this->assertArrayHasKey('author_url', $errors);
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(): array
    {
        return [
            'schema_version' => 'trix.module.v1',
            'id' => 'official.cms',
            'name' => 'CMS',
            'version' => '1.0.0',
            'type' => 'contract',
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

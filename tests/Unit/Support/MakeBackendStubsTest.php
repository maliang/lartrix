<?php

namespace Lartrix\Tests\Unit\Support;

use Lartrix\Tests\TestCase;

class MakeBackendStubsTest extends TestCase
{
    protected function stub(string $name): string
    {
        return file_get_contents(__DIR__ . '/../../../stubs/backend/' . $name);
    }

    /** @test */
    public function auth_controller_has_self_service_methods(): void
    {
        $content = $this->stub('auth_controller.stub');

        $this->assertStringContainsString('profileUi', $content);
        $this->assertStringContainsString('settingsUi', $content);
        $this->assertStringContainsString('passwordUi', $content);
        $this->assertStringContainsString("EmitAction::make('submit')", $content);
        $this->assertStringContainsString("EmitAction::make('close')", $content);
        $this->assertStringContainsString('user->locale', $content);
    }

    /** @test */
    public function routes_has_self_service_and_system_routes(): void
    {
        $content = $this->stub('routes.stub');

        $this->assertStringContainsString('user/profile/ui', $content);
        $this->assertStringContainsString('user/settings', $content);
        $this->assertStringContainsString('user/password', $content);
        $this->assertStringContainsString('theme-config', $content);
        $this->assertStringContainsString('translations', $content);
        $this->assertStringContainsString('locale', $content);
    }

    /** @test */
    public function system_controller_has_theme_locale_translation_support(): void
    {
        $content = $this->stub('system_controller.stub');

        $this->assertStringContainsString('function saveThemeConfig', $content);
        $this->assertStringContainsString("'theme.{{LOWER_NAME}}'", $content);
        $this->assertStringContainsString('function setLocale', $content);
        $this->assertStringContainsString('function translations', $content);
        $this->assertStringContainsString("'submitUrl' => '/locale'", $content);
        $this->assertStringContainsString("'translationsUrl' => '/translations'", $content);
    }

    /** @test */
    public function model_has_admin_user_semantics(): void
    {
        $content = $this->stub('model.stub');

        $this->assertStringContainsString('SoftDeletes', $content);
        $this->assertStringContainsString('function isSuperAdmin', $content);
        $this->assertStringContainsString('function hasActivePermission', $content);
        $this->assertStringContainsString('function isActive', $content);
        $this->assertStringContainsString("where('status', true)", $content);
        $this->assertStringContainsString('last_login_ip', $content);
    }

    /** @test */
    public function seeder_uses_config_model_mapping(): void
    {
        $content = $this->stub('seeder.stub');

        $this->assertStringContainsString("config('lartrix.models.role'", $content);
        $this->assertStringContainsString("config('lartrix.models.permission'", $content);
        $this->assertStringNotContainsString('Spatie\\Permission\\Models\\Role', $content);
        $this->assertStringNotContainsString('Spatie\\Permission\\Models\\Permission', $content);
    }

    /** @test */
    public function migration_has_extra_user_columns(): void
    {
        $content = $this->stub('migration.stub');

        $this->assertStringContainsString('softDeletes()', $content);
        $this->assertStringContainsString('last_login_ip', $content);
        $this->assertStringContainsString('last_login_time', $content);
        $this->assertStringContainsString("'locale', 20", $content);
    }
}

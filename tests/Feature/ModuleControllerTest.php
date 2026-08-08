<?php

namespace Lartrix\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Module;
use Lartrix\Models\Permission;
use Lartrix\Models\Role;
use Lartrix\Tests\TestCase;

class ModuleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $role = Role::create([
            'name' => 'super-admin',
            'title' => 'Super Admin',
            'guard_name' => 'admin',
            'status' => 1,
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'modules.*',
            'title' => 'Modules',
            'guard_name' => 'admin',
        ]);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_modules(): void
    {
        Module::create([
            'name' => 'Blog',
            'title' => 'Blog',
            'enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/modules');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function installed_modules_page_has_module_market_entry(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/modules?action_type=installed_ui');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $payload = json_encode($response->json('data'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->assertStringContainsString('模块市场', $payload);
        $this->assertStringContainsString('上传当前项目', $payload);
        $this->assertStringContainsString('/modules/projects/publish', $payload);
        $this->assertStringContainsString('搜索模块名称、ID 或描述', $payload);
        $this->assertStringContainsString('搜索项目名称、ID 或描述', $payload);
        $this->assertStringContainsString('/modules/market/modules', $payload);
        $this->assertStringContainsString('/modules/market/projects', $payload);
        $this->assertStringContainsString('marketModulePageSize', $payload);
        $this->assertStringContainsString('marketProjectPageSize', $payload);
        $this->assertStringContainsString('showMarketModuleDetail', $payload);
        $this->assertStringContainsString('marketDetailVisible', $payload);
    }

    /** @test */
    public function market_modules_mark_local_registry_modules_as_installed(): void
    {
        config(['lartrix.module_market.url' => 'https://registry.example']);

        Module::create([
            'name' => 'User',
            'title' => 'User',
            'enabled' => true,
            'registry_id' => 'official.user',
            'config' => ['id' => 'official.user'],
        ]);

        Http::fake([
            'https://registry.example/registry/modules*' => Http::response([
                'data' => [
                    'items' => [[
                        'id' => 'official.user',
                        'name' => '用户模块',
                        'summary' => '用户与权限基础能力',
                        'type' => 'core',
                        'latest_version' => '1.0.0',
                        'license' => 'MIT',
                    ]],
                ],
            ]),
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/modules/market/modules?type=core');

        $response->assertStatus(200)
            ->assertJsonPath('data.items.0.id', 'official.user')
            ->assertJsonPath('data.items.0.type', 'core')
            ->assertJsonPath('data.items.0.installed', true)
            ->assertJsonPath('data.items.0.install_status', 'installed')
            ->assertJsonPath('data.items.0.version', '1.0.0')
            ->assertJsonPath('data.page_size', 16);

        Http::assertSent(fn ($request): bool => $request->url() === 'https://registry.example/registry/modules?type=core&language=php&framework=laravel&page=1&page_size=16');
    }

    /** @test */
    public function it_can_publish_the_current_project_from_module_management(): void
    {
        config([
            'lartrix.module_market.url' => 'https://registry.example',
            'lartrix.module_market.auth_key' => 'trx_test',
        ]);

        $manifestPath = base_path('trix-project.json');
        File::put($manifestPath, json_encode([
            'schema_version' => 'trix.project.v1',
            'id' => 'demo.project',
            'name' => 'Demo Project',
            'version' => '1.0.0',
            'author' => 'Demo Author',
            'modules' => [],
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        Http::fake([
            'https://registry.example/registry/auth/me' => Http::response([
                'code' => 200,
                'data' => ['user' => ['name' => 'Demo Author', 'email' => 'demo@example.test']],
            ]),
            'https://registry.example/registry/projects/demo.project/versions*' => Http::response([
                'code' => 200,
                'data' => ['items' => [['version' => '0.9.0']]],
            ]),
            'https://registry.example/registry/publish/projects' => Http::response([
                'code' => 200,
                'msg' => 'success',
                'data' => ['project' => 'demo.project', 'status' => 'pending'],
            ]),
        ]);

        try {
            $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                ->postJson('/api/lartrix/modules/projects/publish');

            $response->assertStatus(200)
                ->assertJsonPath('code', 0)
                ->assertJsonPath('data.project', 'demo.project');

            Http::assertSent(fn ($request): bool => $request->url() === 'https://registry.example/registry/publish/projects');
        } finally {
            File::delete($manifestPath);
        }
    }

    /** @test */
    public function it_can_enable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => 'Blog',
            'enabled' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/modules/' . $module->name . '/enable');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertTrue((bool) $module->fresh()->enabled);
    }

    /** @test */
    public function it_can_disable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => 'Blog',
            'enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/modules/' . $module->name . '/disable');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertFalse((bool) $module->fresh()->enabled);
    }
}

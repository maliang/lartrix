<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Role;
use Lartrix\Models\Permission;
use Lartrix\Models\Module;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class ModuleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = AdminUser::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $role = Role::create([
            'name' => 'super_admin',
            'title' => '超级管理员',
            'guard_name' => 'sanctum',
            'status' => 1,
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'modules.*',
            'title' => '模块管理',
            'guard_name' => 'sanctum',
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
            'title' => '博客模块',
            'enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/modules');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_enable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => '博客模块',
            'enabled' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/modules/' . $module->name . '/enable');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertTrue((bool) $module->fresh()->enabled);
    }

    /** @test */
    public function it_can_disable_module(): void
    {
        $module = Module::create([
            'name' => 'Blog',
            'title' => '博客模块',
            'enabled' => true,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/modules/' . $module->name . '/disable');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertFalse((bool) $module->fresh()->enabled);
    }
}

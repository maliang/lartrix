<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Role;
use Lartrix\Models\Permission;
use Lartrix\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class SettingControllerTest extends TestCase
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
            'name' => 'settings.*',
            'title' => '设置管理',
            'guard_name' => 'sanctum',
        ]);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_settings(): void
    {
        Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Lartrix',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/settings');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_get_settings_by_group(): void
    {
        Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Lartrix',
        ]);

        Setting::create([
            'group' => 'email',
            'key' => 'smtp_host',
            'title' => 'SMTP主机',
            'type' => 'string',
            'value' => 'smtp.example.com',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/settings/group/site');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_update_settings(): void
    {
        Setting::create([
            'group' => 'site',
            'key' => 'site_name',
            'title' => '站点名称',
            'type' => 'string',
            'value' => 'Old Name',
        ]);

        Setting::create([
            'group' => 'site',
            'key' => 'site_logo',
            'title' => '站点Logo',
            'type' => 'string',
            'value' => '/old-logo.png',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/settings', [
                'settings' => [
                    'site_name' => 'New Name',
                    'site_logo' => '/new-logo.png',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('New Name', Setting::get('site_name'));
        $this->assertEquals('/new-logo.png', Setting::get('site_logo'));
    }
}

<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Menu;
use Lartrix\Models\Role;
use Lartrix\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class MenuControllerTest extends TestCase
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
            'name' => 'menus.*',
            'title' => '菜单管理',
            'guard_name' => 'sanctum',
        ]);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_get_user_menus_in_menu_route_format(): void
    {
        Menu::create([
            'name' => 'dashboard',
            'path' => '/dashboard',
            'title' => '仪表盘',
            'component' => 'layout.base',
            'icon' => 'mdi:view-dashboard',
            'sort' => 1,
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/menus');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'name',
                        'path',
                        'meta' => ['title'],
                    ],
                ],
            ]);
    }

    /** @test */
    public function it_can_get_all_menus_for_management(): void
    {
        Menu::create([
            'name' => 'dashboard',
            'path' => '/dashboard',
            'title' => '仪表盘',
            'component' => 'layout.base',
            'sort' => 1,
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/menus/all');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_create_menu(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/menus', [
                'name' => 'new_menu',
                'path' => '/new-menu',
                'title' => '新菜单',
                'component' => 'view.new_menu',
                'sort' => 1,
                'status' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('admin_menus', [
            'name' => 'new_menu',
            'path' => '/new-menu',
        ]);
    }

    /** @test */
    public function it_can_update_menu(): void
    {
        $menu = Menu::create([
            'name' => 'old_menu',
            'path' => '/old-menu',
            'title' => '旧菜单',
            'component' => 'view.old_menu',
            'sort' => 1,
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/menus/' . $menu->id, [
                'title' => '更新后的菜单',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('更新后的菜单', $menu->fresh()->title);
    }

    /** @test */
    public function it_can_delete_menu(): void
    {
        $menu = Menu::create([
            'name' => 'to_delete',
            'path' => '/to-delete',
            'title' => '待删除',
            'component' => 'view.to_delete',
            'sort' => 1,
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/lartrix/menus/' . $menu->id);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseMissing('admin_menus', [
            'id' => $menu->id,
        ]);
    }

    /** @test */
    public function it_can_sort_menus(): void
    {
        $menu1 = Menu::create([
            'name' => 'menu1',
            'path' => '/menu1',
            'title' => '菜单1',
            'component' => 'view.menu1',
            'sort' => 1,
            'status' => 1,
        ]);

        $menu2 = Menu::create([
            'name' => 'menu2',
            'path' => '/menu2',
            'title' => '菜单2',
            'component' => 'view.menu2',
            'sort' => 2,
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/menus/sort', [
                'items' => [
                    ['id' => $menu1->id, 'sort' => 2],
                    ['id' => $menu2->id, 'sort' => 1],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals(2, $menu1->fresh()->sort);
        $this->assertEquals(1, $menu2->fresh()->sort);
    }
}

<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Role;
use Lartrix\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class PermissionControllerTest extends TestCase
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
            'name' => 'permissions.*',
            'title' => '权限管理',
            'guard_name' => 'sanctum',
        ]);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_permissions(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/permissions');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_get_permission_tree(): void
    {
        $parent = Permission::create([
            'name' => 'users',
            'title' => '用户管理',
            'guard_name' => 'sanctum',
        ]);

        Permission::create([
            'name' => 'users.view',
            'title' => '查看用户',
            'guard_name' => 'sanctum',
            'parent_id' => $parent->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/permissions/tree');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_create_permission(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/permissions', [
                'name' => 'posts.view',
                'title' => '查看文章',
                'module' => 'blog',
                'description' => '查看文章列表权限',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'posts.view',
            'title' => '查看文章',
        ]);
    }

    /** @test */
    public function it_can_update_permission(): void
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'title' => '测试权限',
            'guard_name' => 'sanctum',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/permissions/' . $permission->id, [
                'title' => '更新后的权限',
                'description' => '更新后的描述',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('更新后的权限', $permission->fresh()->title);
    }

    /** @test */
    public function it_can_delete_permission(): void
    {
        $permission = Permission::create([
            'name' => 'deletable.permission',
            'title' => '可删除权限',
            'guard_name' => 'sanctum',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/lartrix/permissions/' . $permission->id);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseMissing('permissions', [
            'id' => $permission->id,
        ]);
    }
}

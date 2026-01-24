<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Role;
use Lartrix\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    protected AdminUser $admin;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // 创建管理员用户
        $this->admin = AdminUser::create([
            'name' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        // 创建角色和权限
        $role = Role::create([
            'name' => 'super_admin',
            'title' => '超级管理员',
            'guard_name' => 'sanctum',
            'status' => 1,
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'users.*',
            'title' => '用户管理',
            'guard_name' => 'sanctum',
        ]);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_users(): void
    {
        AdminUser::create([
            'name' => 'user1',
            'email' => 'user1@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/users');

        $response->assertStatus(200)
            ->assertJson(['code' => 0])
            ->assertJsonStructure([
                'data' => [
                    'data',
                    'total',
                ],
            ]);
    }

    /** @test */
    public function it_can_create_user(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/users', [
                'name' => 'newuser',
                'email' => 'newuser@example.com',
                'password' => 'password123',
                'status' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('admin_users', [
            'name' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    /** @test */
    public function it_can_show_user(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/users/' . $user->id);

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'data' => [
                    'id' => $user->id,
                    'name' => 'testuser',
                ],
            ]);
    }

    /** @test */
    public function it_can_update_user(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/users/' . $user->id, [
                'name' => 'updateduser',
                'nick_name' => 'Updated User',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('admin_users', [
            'id' => $user->id,
            'name' => 'updateduser',
        ]);
    }

    /** @test */
    public function it_can_delete_user(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/lartrix/users/' . $user->id);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseMissing('admin_users', [
            'id' => $user->id,
        ]);
    }

    /** @test */
    public function it_can_update_user_status(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        // 创建用户的 token
        $user->createToken('user-token');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson('/api/lartrix/users/' . $user->id . '/status', [
                'status' => 0,
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        // 验证状态已更新
        $this->assertEquals(0, $user->fresh()->status);

        // 验证 token 已被撤销
        $this->assertCount(0, $user->fresh()->tokens);
    }

    /** @test */
    public function it_can_reset_user_password(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'testuser@example.com',
            'password' => Hash::make('oldpassword'),
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->patchJson('/api/lartrix/users/' . $user->id . '/password', [
                'password' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        // 验证新密码可以使用
        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }
}

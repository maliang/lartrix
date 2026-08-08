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
            'username' => 'admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $role = Role::create([
            'name' => 'super-admin',
            'title' => '瓒呯骇绠＄悊鍛?',
            'guard_name' => 'admin',
            'status' => 1,
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'permissions.*',
            'title' => '鏉冮檺绠＄悊',
            'guard_name' => 'admin',
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
            'title' => '鐢ㄦ埛绠＄悊',
            'guard_name' => 'admin',
        ]);

        Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'admin',
            'parent_id' => $parent->id,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/permissions?action_type=tree');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_create_permission(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/permissions', [
                'name' => 'posts.view',
                'title' => '鏌ョ湅鏂囩珷',
                'module' => 'blog',
                'description' => '鏌ョ湅鏂囩珷鍒楄〃鏉冮檺',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'posts.view',
            'title' => '鏌ョ湅鏂囩珷',
        ]);
    }

    /** @test */
    public function it_can_update_permission(): void
    {
        $permission = Permission::create([
            'name' => 'test.permission',
            'title' => '娴嬭瘯鏉冮檺',
            'guard_name' => 'admin',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/permissions/' . $permission->id, [
                'title' => '鏇存柊鍚庣殑鏉冮檺',
                'description' => '鏇存柊鍚庣殑鎻忚堪',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('鏇存柊鍚庣殑鏉冮檺', $permission->fresh()->title);
    }

    /** @test */
    public function it_can_delete_permission(): void
    {
        $permission = Permission::create([
            'name' => 'deletable.permission',
            'title' => '鍙垹闄ゆ潈闄?',
            'guard_name' => 'admin',
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

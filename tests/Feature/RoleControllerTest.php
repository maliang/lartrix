<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Lartrix\Models\Role;
use Lartrix\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class RoleControllerTest extends TestCase
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
            'title' => '瓒呯骇绠＄悊鍛?',
            'guard_name' => 'sanctum',
            'status' => 1,
            'is_system' => true,
        ]);

        $permission = Permission::create([
            'name' => 'roles.*',
            'title' => '瑙掕壊绠＄悊',
            'guard_name' => 'sanctum',
        ]);

        $role->givePermissionTo($permission);
        $this->admin->assignRole($role);

        $this->token = $this->admin->createToken('test-token')->plainTextToken;
    }

    /** @test */
    public function it_can_list_roles(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->getJson('/api/lartrix/roles');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_create_role(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/roles', [
                'name' => 'editor',
                'title' => '缂栬緫',
                'description' => '鍐呭缂栬緫瑙掕壊',
                'status' => 1,
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseHas('roles', [
            'name' => 'editor',
            'title' => '缂栬緫',
        ]);
    }

    /** @test */
    public function it_can_update_role(): void
    {
        $role = Role::create([
            'name' => 'test_role',
            'title' => '娴嬭瘯瑙掕壊',
            'guard_name' => 'sanctum',
            'status' => 1,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->putJson('/api/lartrix/roles/' . $role->id, [
                'title' => '鏇存柊鍚庣殑瑙掕壊',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('鏇存柊鍚庣殑瑙掕壊', $role->fresh()->title);
    }

    /** @test */
    public function it_cannot_delete_system_role(): void
    {
        $systemRole = Role::where('is_system', true)->first();

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/lartrix/roles/' . $systemRole->id);

        // 绯荤粺瑙掕壊涓嶈兘鍒犻櫎
        $response->assertJson(['code' => 1]);

        $this->assertDatabaseHas('roles', [
            'id' => $systemRole->id,
        ]);
    }

    /** @test */
    public function it_can_delete_non_system_role(): void
    {
        $role = Role::create([
            'name' => 'deletable_role',
            'title' => '鍙垹闄よ鑹?',
            'guard_name' => 'sanctum',
            'status' => 1,
            'is_system' => false,
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->deleteJson('/api/lartrix/roles/' . $role->id);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertDatabaseMissing('roles', [
            'id' => $role->id,
        ]);
    }

    /** @test */
    public function it_can_assign_permissions_to_role(): void
    {
        $role = Role::create([
            'name' => 'test_role',
            'title' => '娴嬭瘯瑙掕壊',
            'guard_name' => 'sanctum',
            'status' => 1,
        ]);

        $permission1 = Permission::create([
            'name' => 'test.permission1',
            'title' => '娴嬭瘯鏉冮檺1',
            'guard_name' => 'sanctum',
        ]);

        $permission2 = Permission::create([
            'name' => 'test.permission2',
            'title' => '娴嬭瘯鏉冮檺2',
            'guard_name' => 'sanctum',
        ]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/lartrix/roles/' . $role->id . '/permissions', [
                'permissions' => [$permission1->id, $permission2->id],
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertTrue($role->fresh()->hasPermissionTo('test.permission1'));
        $this->assertTrue($role->fresh()->hasPermissionTo('test.permission2'));
    }
}

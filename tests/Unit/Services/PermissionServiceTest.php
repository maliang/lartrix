<?php

namespace Lartrix\Tests\Unit\Services;

use Lartrix\Tests\TestCase;
use Lartrix\Services\PermissionService;
use Lartrix\Models\Permission;
use Lartrix\Models\Role;
use Lartrix\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PermissionService $permissionService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->permissionService = new PermissionService();
    }

    /** @test */
    public function it_can_get_permission_tree(): void
    {
        $parent = Permission::create([
            'name' => 'users',
            'title' => '鐢ㄦ埛绠＄悊',
            'guard_name' => 'sanctum',
            'module' => 'system',
        ]);

        Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'module' => 'system',
            'parent_id' => $parent->id,
        ]);

        $tree = $this->permissionService->getTree();

        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
    }

    /** @test */
    public function it_can_get_permissions_grouped_by_module(): void
    {
        Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'module' => 'system',
        ]);

        Permission::create([
            'name' => 'posts.view',
            'title' => '鏌ョ湅鏂囩珷',
            'guard_name' => 'sanctum',
            'module' => 'blog',
        ]);

        $grouped = $this->permissionService->getGroupedByModule();

        $this->assertArrayHasKey('system', $grouped);
        $this->assertArrayHasKey('blog', $grouped);
    }

    /** @test */
    public function it_excludes_disabled_role_permissions(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $activeRole = Role::create([
            'name' => 'active_role',
            'title' => '鍚敤瑙掕壊',
            'guard_name' => 'sanctum',
            'status' => 1,
        ]);

        $disabledRole = Role::create([
            'name' => 'disabled_role',
            'title' => '绂佺敤瑙掕壊',
            'guard_name' => 'sanctum',
            'status' => 0,
        ]);

        $permission1 = Permission::create([
            'name' => 'permission1',
            'title' => '鏉冮檺1',
            'guard_name' => 'sanctum',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'title' => '鏉冮檺2',
            'guard_name' => 'sanctum',
        ]);

        $activeRole->givePermissionTo($permission1);
        $disabledRole->givePermissionTo($permission2);

        $user->assignRole($activeRole);
        $user->assignRole($disabledRole);

        $permissions = $this->permissionService->getUserPermissions($user);

        // 鍙簲璇ュ寘鍚惎鐢ㄨ鑹茬殑鏉冮檺
        $this->assertTrue($permissions->contains('name', 'permission1'));
        $this->assertFalse($permissions->contains('name', 'permission2'));
    }
}

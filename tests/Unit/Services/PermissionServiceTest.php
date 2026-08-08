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
            'title' => '用户管理',
            'guard_name' => 'admin',
            'module' => 'system',
        ]);

        Permission::create([
            'name' => 'users.view',
            'title' => '查看用户',
            'guard_name' => 'admin',
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
            'title' => '查看用户',
            'guard_name' => 'admin',
            'module' => 'system',
        ]);

        Permission::create([
            'name' => 'posts.view',
            'title' => '查看文章',
            'guard_name' => 'admin',
            'module' => 'blog',
        ]);

        $grouped = $this->permissionService->getTreeByModule();

        $this->assertArrayHasKey('system', $grouped);
        $this->assertArrayHasKey('blog', $grouped);
    }

    /** @test */
    public function it_excludes_disabled_role_permissions(): void
    {
        $user = AdminUser::create([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $activeRole = Role::create([
            'name' => 'active_role',
            'title' => '启用角色',
            'guard_name' => 'admin',
            'status' => 1,
        ]);

        $disabledRole = Role::create([
            'name' => 'disabled_role',
            'title' => '禁用角色',
            'guard_name' => 'admin',
            'status' => 0,
        ]);

        $permission1 = Permission::create([
            'name' => 'permission1',
            'title' => '权限1',
            'guard_name' => 'admin',
        ]);

        $permission2 = Permission::create([
            'name' => 'permission2',
            'title' => '权限2',
            'guard_name' => 'admin',
        ]);

        $activeRole->givePermissionTo($permission1);
        $disabledRole->givePermissionTo($permission2);

        $user->assignRole($activeRole);
        $user->assignRole($disabledRole);

        $permissions = $this->permissionService->getUserActivePermissions($user);

        // 应只包含启用角色的权限
        $this->assertContains('permission1', $permissions);
        $this->assertNotContains('permission2', $permissions);
    }
}

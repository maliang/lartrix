<?php

namespace Lartrix\Tests\Unit\Models;

use Lartrix\Tests\TestCase;
use Lartrix\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PermissionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_permission(): void
    {
        $permission = Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'module' => 'users',
            'description' => '鏌ョ湅鐢ㄦ埛鍒楄〃鏉冮檺',
            'sort' => 1,
        ]);

        $this->assertDatabaseHas('permissions', [
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'module' => 'users',
        ]);
    }

    /** @test */
    public function it_supports_parent_child_hierarchy(): void
    {
        $parent = Permission::create([
            'name' => 'users',
            'title' => '鐢ㄦ埛绠＄悊',
            'guard_name' => 'sanctum',
            'module' => 'users',
        ]);

        $child = Permission::create([
            'name' => 'users.create',
            'title' => '鍒涘缓鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'module' => 'users',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
    }

    /** @test */
    public function it_can_get_children_permissions(): void
    {
        $parent = Permission::create([
            'name' => 'users',
            'title' => '鐢ㄦ埛绠＄悊',
            'guard_name' => 'sanctum',
        ]);

        Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'parent_id' => $parent->id,
        ]);

        Permission::create([
            'name' => 'users.create',
            'title' => '鍒涘缓鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'parent_id' => $parent->id,
        ]);

        $this->assertCount(2, $parent->children);
    }

    /** @test */
    public function it_can_get_parent_permission(): void
    {
        $parent = Permission::create([
            'name' => 'users',
            'title' => '鐢ㄦ埛绠＄悊',
            'guard_name' => 'sanctum',
        ]);

        $child = Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'parent_id' => $parent->id,
        ]);

        $this->assertEquals($parent->id, $child->parent->id);
    }

    /** @test */
    public function it_can_build_permission_tree(): void
    {
        $parent = Permission::create([
            'name' => 'users',
            'title' => '鐢ㄦ埛绠＄悊',
            'guard_name' => 'sanctum',
        ]);

        Permission::create([
            'name' => 'users.view',
            'title' => '鏌ョ湅鐢ㄦ埛',
            'guard_name' => 'sanctum',
            'parent_id' => $parent->id,
        ]);

        $tree = Permission::getTree();
        
        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
    }
}

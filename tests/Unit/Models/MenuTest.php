<?php

namespace Lartrix\Tests\Unit\Models;

use Lartrix\Tests\TestCase;
use Lartrix\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;

class MenuTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_menu(): void
    {
        $menu = Menu::create([
            'name' => 'dashboard',
            'path' => '/dashboard',
            'title' => '仪表盘',
            'component' => 'layout.base',
            'sort' => 1,
            'status' => 1,
        ]);

        $this->assertDatabaseHas('admin_menus', [
            'name' => 'dashboard',
            'path' => '/dashboard',
        ]);
    }

    /** @test */
    public function it_can_convert_to_menu_route_format(): void
    {
        $menu = Menu::create([
            'name' => 'dashboard',
            'path' => '/dashboard',
            'title' => '仪表盘',
            'component' => 'layout.base',
            'icon' => 'mdi:view-dashboard',
            'sort' => 1,
            'status' => 1,
        ]);

        $menuRoute = $menu->toMenuRoute();

        $this->assertIsArray($menuRoute);
        $this->assertEquals('dashboard', $menuRoute['name']);
        $this->assertEquals('/dashboard', $menuRoute['path']);
        $this->assertArrayHasKey('meta', $menuRoute);
        $this->assertEquals('仪表盘', $menuRoute['meta']['title']);
    }

    /** @test */
    public function it_supports_parent_child_hierarchy(): void
    {
        $parent = Menu::create([
            'name' => 'system',
            'path' => '/system',
            'title' => '系统管理',
            'component' => 'layout.base',
            'sort' => 1,
            'status' => 1,
        ]);

        $child = Menu::create([
            'name' => 'system_user',
            'path' => '/system/user',
            'title' => '用户管理',
            'component' => 'view.system_user',
            'parent_id' => $parent->id,
            'sort' => 1,
            'status' => 1,
        ]);

        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertCount(1, $parent->children);
    }

    /** @test */
    public function it_can_get_menu_tree(): void
    {
        $parent = Menu::create([
            'name' => 'system',
            'path' => '/system',
            'title' => '系统管理',
            'component' => 'layout.base',
            'sort' => 1,
            'status' => 1,
        ]);

        Menu::create([
            'name' => 'system_user',
            'path' => '/system/user',
            'title' => '用户管理',
            'component' => 'view.system_user',
            'parent_id' => $parent->id,
            'sort' => 1,
            'status' => 1,
        ]);

        $tree = Menu::getTree();
        
        $this->assertIsArray($tree);
        $this->assertNotEmpty($tree);
    }
}

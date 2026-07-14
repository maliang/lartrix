<?php

namespace Lartrix\Tests\Unit\Models;

use Lartrix\Tests\TestCase;
use Lartrix\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_role(): void
    {
        $role = Role::create([
            'name' => 'admin',
            'title' => '绠＄悊鍛?',
            'guard_name' => 'sanctum',
            'description' => '绯荤粺绠＄悊鍛樿鑹?',
            'status' => 1,
            'is_system' => true,
        ]);

        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'title' => '绠＄悊鍛?',
            'is_system' => true,
        ]);
    }

    /** @test */
    public function it_has_system_role_flag(): void
    {
        $systemRole = Role::create([
            'name' => 'super_admin',
            'title' => '瓒呯骇绠＄悊鍛?',
            'guard_name' => 'sanctum',
            'is_system' => true,
        ]);

        $normalRole = Role::create([
            'name' => 'editor',
            'title' => '缂栬緫',
            'guard_name' => 'sanctum',
            'is_system' => false,
        ]);

        $this->assertTrue((bool) $systemRole->is_system);
        $this->assertFalse((bool) $normalRole->is_system);
    }

    /** @test */
    public function it_has_status_field(): void
    {
        $activeRole = Role::create([
            'name' => 'active_role',
            'title' => '鍚敤瑙掕壊',
            'guard_name' => 'sanctum',
            'status' => 1,
        ]);

        $inactiveRole = Role::create([
            'name' => 'inactive_role',
            'title' => '绂佺敤瑙掕壊',
            'guard_name' => 'sanctum',
            'status' => 0,
        ]);

        $this->assertEquals(1, $activeRole->status);
        $this->assertEquals(0, $inactiveRole->status);
    }
}

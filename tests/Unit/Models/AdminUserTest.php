<?php

namespace Lartrix\Tests\Unit\Models;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AdminUserTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_admin_user(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'nick_name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $this->assertDatabaseHas('admin_users', [
            'name' => 'testuser',
            'email' => 'test@example.com',
        ]);
    }

    /** @test */
    public function it_has_api_tokens_trait(): void
    {
        $user = new AdminUser();
        
        $this->assertTrue(
            method_exists($user, 'createToken'),
            'AdminUser 应该有 createToken 方法（HasApiTokens trait）'
        );
    }

    /** @test */
    public function it_has_roles_trait(): void
    {
        $user = new AdminUser();
        
        $this->assertTrue(
            method_exists($user, 'assignRole'),
            'AdminUser 应该有 assignRole 方法（HasRoles trait）'
        );
        
        $this->assertTrue(
            method_exists($user, 'hasRole'),
            'AdminUser 应该有 hasRole 方法（HasRoles trait）'
        );
    }

    /** @test */
    public function it_can_check_user_status(): void
    {
        $activeUser = AdminUser::create([
            'name' => 'active',
            'email' => 'active@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $inactiveUser = AdminUser::create([
            'name' => 'inactive',
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'status' => 0,
        ]);

        $this->assertEquals(1, $activeUser->status);
        $this->assertEquals(0, $inactiveUser->status);
    }

    /** @test */
    public function it_hides_password_in_array(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'status' => 1,
        ]);

        $array = $user->toArray();
        
        $this->assertArrayNotHasKey('password', $array);
    }
}

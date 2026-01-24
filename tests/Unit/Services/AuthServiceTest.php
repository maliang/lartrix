<?php

namespace Lartrix\Tests\Unit\Services;

use Lartrix\Tests\TestCase;
use Lartrix\Services\AuthService;
use Lartrix\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    /** @test */
    public function it_can_login_with_valid_credentials(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $result = $this->authService->login('test@example.com', 'password');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user->id, $result['user']['id']);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials(): void
    {
        AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $this->expectException(\Lartrix\Exceptions\ApiException::class);
        
        $this->authService->login('test@example.com', 'wrong_password');
    }

    /** @test */
    public function it_fails_login_for_disabled_user(): void
    {
        AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 0, // 禁用状态
        ]);

        $this->expectException(\Lartrix\Exceptions\ApiException::class);
        
        $this->authService->login('test@example.com', 'password');
    }

    /** @test */
    public function it_can_logout_user(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        // 创建 token
        $token = $user->createToken('test-token');
        
        // 模拟认证
        $this->actingAs($user, 'sanctum');

        $this->authService->logout($user);

        // 验证 token 已被撤销
        $this->assertCount(0, $user->tokens);
    }

    /** @test */
    public function it_can_refresh_token(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $oldToken = $user->createToken('old-token');
        $this->actingAs($user, 'sanctum');

        $result = $this->authService->refresh($user);

        $this->assertArrayHasKey('token', $result);
    }

    /** @test */
    public function it_can_get_user_tokens(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $user->createToken('token-1');
        $user->createToken('token-2');

        $tokens = $this->authService->getTokens($user);

        $this->assertCount(2, $tokens);
    }

    /** @test */
    public function it_can_revoke_specific_token(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');

        $this->authService->revokeToken($user, $token1->accessToken->id);

        $this->assertCount(1, $user->fresh()->tokens);
    }
}

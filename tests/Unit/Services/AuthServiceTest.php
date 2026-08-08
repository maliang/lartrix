<?php

namespace Lartrix\Tests\Unit\Services;

use Lartrix\Tests\TestCase;
use Lartrix\Services\AuthService;
use Lartrix\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    protected function createUser(array $attributes = []): AdminUser
    {
        return AdminUser::create(array_merge([
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ], $attributes));
    }

    /** @test */
    public function it_can_login_with_valid_credentials(): void
    {
        $user = $this->createUser();

        $result = $this->authService->login('testuser', 'password');

        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('user', $result);
        $this->assertEquals($user->id, $result['user']['id']);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials(): void
    {
        $this->createUser();

        $this->assertNull($this->authService->login('testuser', 'wrong_password'));
    }

    /** @test */
    public function it_fails_login_for_disabled_user(): void
    {
        $this->createUser(['status' => 0]);

        $this->assertNull($this->authService->login('testuser', 'password'));
    }

    /** @test */
    public function it_can_logout_user(): void
    {
        $user = $this->createUser();

        // 未认证（无当前 token）时 logout 返回 false 且不抛异常
        $this->assertFalse($this->authService->logout($user));
    }

    /** @test */
    public function it_can_refresh_token(): void
    {
        $user = $this->createUser();

        $oldToken = $user->createToken('old-token');
        $this->actingAs($user, 'sanctum');

        $result = $this->authService->refresh($user);

        $this->assertInstanceOf(NewAccessToken::class, $result);
    }

    /** @test */
    public function it_can_get_user_tokens(): void
    {
        $user = $this->createUser();

        $user->createToken('token-1');
        $user->createToken('token-2');

        $tokens = $this->authService->getTokens($user);

        $this->assertCount(2, $tokens);
    }

    /** @test */
    public function it_can_revoke_specific_token(): void
    {
        $user = $this->createUser();

        $token1 = $user->createToken('token-1');
        $token2 = $user->createToken('token-2');

        $this->authService->revokeToken($user, $token1->accessToken->id);

        $this->assertCount(1, $user->fresh()->tokens);
    }
}

<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

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

        $response = $this->postJson('/api/lartrix/auth/login', [
            'username' => 'testuser',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'msg',
                'data' => [
                    'token',
                ],
            ])
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials(): void
    {
        $this->createUser();

        $response = $this->postJson('/api/lartrix/auth/login', [
            'username' => 'testuser',
            'password' => 'wrong_password',
        ]);

        $response->assertJson(['code' => 40001]);
    }

    /** @test */
    public function it_can_logout(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_get_current_user(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/lartrix/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'data' => [
                    'id' => $user->id,
                    'username' => 'testuser',
                    'email' => 'test@example.com',
                ],
            ]);
    }

    /** @test */
    public function it_requires_authentication_for_protected_routes(): void
    {
        $response = $this->getJson('/api/lartrix/auth/user');

        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_refresh_token(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/auth/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'msg',
                'data' => ['token'],
            ]);
    }

    /** @test */
    public function it_can_get_profile_ui(): void
    {
        $user = $this->createUser(['nickname' => '原始昵称']);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/lartrix/user/profile/ui');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
        $this->assertNotEmpty($response->json('data'));
    }

    /** @test */
    public function it_can_update_profile(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/user/profile', [
                'nickname' => '新昵称',
                'email' => 'new@example.com',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('新昵称', $user->fresh()->nickname);
        $this->assertEquals('new@example.com', $user->fresh()->email);
    }

    /** @test */
    public function it_can_change_password(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/user/password', [
                'current_password' => 'password',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    /** @test */
    public function it_fails_change_password_with_wrong_current(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/user/password', [
                'current_password' => 'wrong',
                'new_password' => 'newpassword123',
                'new_password_confirmation' => 'newpassword123',
            ]);

        $response->assertJson(['code' => 40022]);
    }

    /** @test */
    public function it_can_update_locale_settings(): void
    {
        $user = $this->createUser();

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/user/settings', [
                'locale' => 'en-US',
            ]);

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);

        $this->assertEquals('en-US', $user->fresh()->locale);
    }
}

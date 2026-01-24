<?php

namespace Lartrix\Tests\Feature;

use Lartrix\Tests\TestCase;
use Lartrix\Models\AdminUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_login_with_valid_credentials(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $response = $this->postJson('/api/lartrix/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'code',
                'msg',
                'data' => [
                    'token',
                    'user',
                ],
            ])
            ->assertJson(['code' => 0]);
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

        $response = $this->postJson('/api/lartrix/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrong_password',
        ]);

        $response->assertJson(['code' => 1]);
    }

    /** @test */
    public function it_can_logout(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/lartrix/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['code' => 0]);
    }

    /** @test */
    public function it_can_get_current_user(): void
    {
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/lartrix/auth/user');

        $response->assertStatus(200)
            ->assertJson([
                'code' => 0,
                'data' => [
                    'id' => $user->id,
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
        $user = AdminUser::create([
            'name' => 'testuser',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'status' => 1,
        ]);

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
}

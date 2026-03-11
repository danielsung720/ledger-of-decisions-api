<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function UserCanLoginWithValidCredentials(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '登入成功',
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'user' => ['id', 'name', 'email'],
                    'token',
                    'token_type',
                ],
                'message',
            ]);
    }

    #[Test]
    public function LoginFailsWithWrongPassword(): void
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Email 或密碼不正確',
            ]);
    }

    #[Test]
    public function LoginFailsWithNonexistentEmail(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => 'Email 或密碼不正確',
            ]);
    }

    #[Test]
    public function UnverifiedUserLoginReturnsEmailNotVerifiedError(): void
    {
        $user = User::factory()->unverified()->create([
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'unverified@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'error' => 'email_not_verified',
                'data' => [
                    'email' => 'unverified@example.com',
                ],
            ]);
    }

    #[Test]
    public function UnverifiedUserLoginReturnsCorrectEmailInResponse(): void
    {
        $testEmail = 'another-unverified@example.com';

        User::factory()->unverified()->create([
            'email' => $testEmail,
            'password' => 'password123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $testEmail,
            'password' => 'password123',
        ]);

        $response->assertStatus(403);

        $responseData = $response->json();
        $this->assertEquals('email_not_verified', $responseData['error']);
        $this->assertEquals($testEmail, $responseData['data']['email']);
    }

    #[Test]
    public function LoginRequiresEmail(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    #[Test]
    public function LoginRequiresPassword(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
    }
}

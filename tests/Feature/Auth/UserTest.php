<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function GetUserRequiresAuthentication(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401);
    }

    #[Test]
    public function BrowserLikeRequestToApiStillReturnsJsonWhenUnauthenticated(): void
    {
        $response = $this->withHeader('Accept', 'text/html')
            ->get('/api/user');

        $response->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
                'status_code' => 401,
            ]);
    }

    #[Test]
    public function AuthenticatedUserCanGetProfile(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'name' => 'Test User',
                    'email' => 'test@example.com',
                ],
            ]);
    }

    #[Test]
    public function UserResponseIncludesVerificationStatus(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                    'created_at',
                ],
            ]);
    }

    #[Test]
    public function LogoutRequiresAuthentication(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    #[Test]
    public function AuthenticatedUserCanLogout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => '登出成功',
            ]);
    }

    #[Test]
    public function LogoutRevokesCurrentToken(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $this->assertEquals(1, $user->tokens()->count());

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $this->assertEquals(0, $user->tokens()->count());
    }

    #[Test]
    public function LogoutOnlyRevokesCurrentToken(): void
    {
        $user = User::factory()->create();
        $token1 = $user->createToken('token_1')->plainTextToken;
        $user->createToken('token_2');

        $this->assertEquals(2, $user->tokens()->count());

        $this->withHeader('Authorization', "Bearer {$token1}")
            ->postJson('/api/logout');

        $this->assertEquals(1, $user->tokens()->count());
    }

}

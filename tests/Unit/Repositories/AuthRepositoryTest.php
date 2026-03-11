<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Auth\RegisterDto;
use App\Models\User;
use App\Repositories\AuthRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private AuthRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new AuthRepository();
    }

    #[Test]
    public function CreateUserShouldPersistUser(): void
    {
        $user = $this->repository->createUser(
            new RegisterDto(name: 'Repo User', email: 'repo@example.com', password: 'password123')
        );

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'repo@example.com',
            'name' => 'Repo User',
        ]);
    }

    #[Test]
    public function FindUserByEmailShouldReturnUser(): void
    {
        $user = User::factory()->create(['email' => 'find@example.com']);

        $result = $this->repository->findUserByEmail('find@example.com');

        $this->assertNotNull($result);
        $this->assertSame($user->id, $result->id);
    }

    #[Test]
    public function MarkEmailVerifiedShouldSetTimestamp(): void
    {
        $user = User::factory()->unverified()->create();

        $updated = $this->repository->markEmailVerified($user);

        $this->assertNotNull($updated->email_verified_at);
    }

    #[Test]
    public function CreateAuthTokenShouldCreateToken(): void
    {
        $user = User::factory()->create();

        $token = $this->repository->createAuthToken($user);

        $this->assertIsString($token);
        $this->assertSame(1, $user->tokens()->count());
    }

    #[Test]
    public function DeleteCurrentAccessTokenShouldDeleteOnlyGivenToken(): void
    {
        $user = User::factory()->create();
        $tokenA = $user->createToken('a')->accessToken;
        $tokenB = $user->createToken('b')->accessToken;

        $this->repository->deleteCurrentAccessToken($user, $tokenA);

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenA->id]);
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $tokenB->id]);
    }

    #[Test]
    public function DeleteCurrentAccessTokenShouldIgnoreNullToken(): void
    {
        $user = User::factory()->create();
        $user->createToken('a');

        $this->repository->deleteCurrentAccessToken($user, null);

        $this->assertSame(1, $user->tokens()->count());
    }

    #[Test]
    public function UpdatePasswordShouldPersistHashedPassword(): void
    {
        $user = User::factory()->create(['password' => 'oldpassword']);

        $updated = $this->repository->updatePassword($user, 'newpassword123');

        $this->assertTrue(Hash::check('newpassword123', $updated->password));
    }

    #[Test]
    public function RevokeAllTokensShouldDeleteAllUserTokens(): void
    {
        $user = User::factory()->create();
        $user->createToken('a');
        $user->createToken('b');

        $this->repository->revokeAllTokens($user);

        $this->assertSame(0, $user->tokens()->count());
    }
}

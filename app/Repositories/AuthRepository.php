<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Auth\RegisterDto;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Persistence operations for authentication and user account state.
 */
class AuthRepository
{
    /**
     * Create a new user row.
     */
    public function createUser(RegisterDto $payload): User
    {
        return User::create([
            'name' => $payload->name,
            'email' => $payload->email,
            'password' => $payload->password,
        ]);
    }

    /**
     * Find user by email.
     */
    public function findUserByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    /**
     * Mark user's email as verified.
     */
    public function markEmailVerified(User $user): User
    {
        $user->email_verified_at = now();
        $user->save();

        return $user;
    }

    /**
     * Create auth token for API usage.
     */
    public function createAuthToken(User $user): string
    {
        return $user->createToken('auth_token')->plainTextToken;
    }

    /**
     * Revoke current access token.
     */
    public function deleteCurrentAccessToken(User $user, ?PersonalAccessToken $token): void
    {
        if ($token === null) {
            return;
        }

        $user->tokens()->whereKey($token->getKey())->delete();
    }

    /**
     * Update user's password.
     */
    public function updatePassword(User $user, string $password): User
    {
        $user->password = $password;
        $user->save();

        return $user;
    }

    /**
     * Revoke all issued tokens.
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}

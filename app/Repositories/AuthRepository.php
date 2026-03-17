<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Auth\RegisterDto;
use App\Models\User;

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
     * Update user's password.
     */
    public function updatePassword(User $user, string $password): User
    {
        $user->password = $password;
        $user->save();

        return $user;
    }
}

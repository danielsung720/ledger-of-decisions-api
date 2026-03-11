<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Data object for login request payload.
 */
final readonly class LoginDto
{
    /**
     * @param  string  $email  Login email.
     * @param  string  $password  Plain-text password from request.
     */
    public function __construct(
        public string $email,
        public string $password
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{email: string, password: string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            email: $payload['email'],
            password: $payload['password'],
        );
    }
}

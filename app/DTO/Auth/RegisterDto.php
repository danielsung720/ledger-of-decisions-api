<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Data object for registration request payload.
 */
final readonly class RegisterDto
{
    /**
     * @param  string  $name  Display name.
     * @param  string  $email  Registration email.
     * @param  string  $password  Hashed by model cast before persistence.
     */
    public function __construct(
        public string $name,
        public string $email,
        public string $password
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{name: string, email: string, password: string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            name: $payload['name'],
            email: $payload['email'],
            password: $payload['password'],
        );
    }
}

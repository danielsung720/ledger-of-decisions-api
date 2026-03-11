<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Data object for password-reset request payload.
 */
final readonly class ResetPasswordDto
{
    /**
     * @param  string  $email  Account email.
     * @param  string  $code  One-time verification code.
     * @param  string  $password  New password.
     */
    public function __construct(
        public string $email,
        public string $code,
        public string $password
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{email: string, code: string, password: string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            email: $payload['email'],
            code: $payload['code'],
            password: $payload['password'],
        );
    }
}

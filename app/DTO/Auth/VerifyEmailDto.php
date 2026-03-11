<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Data object for email-verification payload.
 */
final readonly class VerifyEmailDto
{
    /**
     * @param  string  $email  Account email.
     * @param  string  $code  One-time verification code.
     */
    public function __construct(
        public string $email,
        public string $code
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{email: string, code: string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            email: $payload['email'],
            code: $payload['code'],
        );
    }
}

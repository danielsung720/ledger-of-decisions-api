<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Data object containing only email field.
 */
final readonly class EmailOnlyDto
{
    /**
     * @param  string  $email  Target email address.
     */
    public function __construct(
        public string $email
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{email: string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            email: $payload['email'],
        );
    }
}

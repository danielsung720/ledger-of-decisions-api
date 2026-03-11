<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Data object for authenticated password update payload.
 */
final readonly class UpdatePasswordDto
{
    /**
     * @param  string  $password  New password.
     */
    public function __construct(
        public string $password
    ) {
    }

    /**
     * Build DTO from validated request payload.
     *
     * @param  array{password: string}  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            password: $payload['password'],
        );
    }
}

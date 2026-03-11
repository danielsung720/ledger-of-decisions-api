<?php

declare(strict_types=1);

namespace App\DTO\Auth;

/**
 * Unified result object for auth operations and API response mapping.
 */
final readonly class AuthOperationResultDto
{
    /**
     * @param  bool  $success  Whether operation succeeded.
     * @param  int  $statusCode  HTTP status code for response.
     * @param  string|null  $message  Success message.
     * @param  string|null  $error  Error message or code.
     * @param  array<string, mixed>|null  $data
     */
    public function __construct(
        public bool $success,
        public int $statusCode,
        public ?string $message = null,
        public ?string $error = null,
        public ?array $data = null
    ) {
    }

    /**
     * Convert DTO to standard API response payload.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $result = ['success' => $this->success];

        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        if ($this->message !== null) {
            $result['message'] = $this->message;
        }

        if ($this->error !== null) {
            $result['error'] = $this->error;
        }

        return $result;
    }
}

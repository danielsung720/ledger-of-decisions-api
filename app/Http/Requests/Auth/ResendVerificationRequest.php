<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\EmailOnlyDto;
use App\Http\Requests\ApiFormRequest;

/**
 * Validate payload for resending verification code.
 */
class ResendVerificationRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
        ];
    }

    /**
     * Convert validated payload into email-only DTO.
     */
    public function toDto(): EmailOnlyDto
    {
        /** @var array{email: string} $validated */
        $validated = $this->validated();

        return EmailOnlyDto::fromArray($validated);
    }
}

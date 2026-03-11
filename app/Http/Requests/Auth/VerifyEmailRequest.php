<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\VerifyEmailDto;
use App\Http\Requests\ApiFormRequest;

/**
 * Validate payload for email verification.
 */
class VerifyEmailRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
            'code.required' => '驗證碼為必填欄位',
            'code.size' => '驗證碼必須為 6 位數',
        ];
    }

    /**
     * Convert validated payload into verify-email DTO.
     */
    public function toDto(): VerifyEmailDto
    {
        /** @var array{email: string, code: string} $validated */
        $validated = $this->validated();

        return VerifyEmailDto::fromArray($validated);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\ResetPasswordDto;
use App\Http\Requests\ApiFormRequest;

/**
 * Validate payload for password reset by verification code.
 */
class ResetPasswordRequest extends ApiFormRequest
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
            'code.required' => '驗證碼為必填欄位',
            'code.size' => '驗證碼必須為 6 位數',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 8 個字元',
            'password.confirmed' => '密碼確認不一致',
        ];
    }

    /**
     * Convert validated payload into reset-password DTO.
     */
    public function toDto(): ResetPasswordDto
    {
        /** @var array{email: string, code: string, password: string} $validated */
        $validated = $this->validated();

        return ResetPasswordDto::fromArray($validated);
    }
}

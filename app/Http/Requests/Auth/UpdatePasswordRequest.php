<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\UpdatePasswordDto;
use App\Http\Requests\ApiFormRequest;

/**
 * Validate payload for authenticated password update.
 */
class UpdatePasswordRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'current_password.required' => '目前密碼為必填欄位',
            'current_password.current_password' => '目前密碼不正確',
            'password.required' => '新密碼為必填欄位',
            'password.min' => '新密碼至少需要 8 個字元',
            'password.confirmed' => '新密碼確認不一致',
        ];
    }

    /**
     * Convert validated payload into update-password DTO.
     */
    public function toDto(): UpdatePasswordDto
    {
        /** @var array{password: string} $validated */
        $validated = $this->validated();

        return UpdatePasswordDto::fromArray($validated);
    }
}

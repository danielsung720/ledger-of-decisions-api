<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\RegisterDto;
use App\Http\Requests\ApiFormRequest;

/**
 * Validate payload for user registration.
 */
class RegisterRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '名稱為必填欄位',
            'name.max' => '名稱不可超過 255 個字元',
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
            'email.unique' => '此 Email 已被註冊',
            'password.required' => '密碼為必填欄位',
            'password.min' => '密碼至少需要 8 個字元',
            'password.confirmed' => '密碼確認不一致',
        ];
    }

    /**
     * Convert validated payload into register DTO.
     */
    public function toDto(): RegisterDto
    {
        /** @var array{name: string, email: string, password: string} $validated */
        $validated = $this->validated();

        return RegisterDto::fromArray($validated);
    }
}

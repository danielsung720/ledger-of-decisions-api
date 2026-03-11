<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use App\DTO\Auth\LoginDto;
use App\Http\Requests\ApiFormRequest;

/**
 * Validate payload for user login.
 */
class LoginRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Email 為必填欄位',
            'email.email' => 'Email 格式不正確',
            'password.required' => '密碼為必填欄位',
        ];
    }

    /**
     * Convert validated payload into login DTO.
     */
    public function toDto(): LoginDto
    {
        /** @var array{email: string, password: string} $validated */
        $validated = $this->validated();

        return LoginDto::fromArray($validated);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

class UpdateUserPreferencesRequest extends ApiFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ui_theme' => ['required', 'string', 'in:default,code,ocean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ui_theme.required' => '主題設定為必填欄位',
            'ui_theme.string' => '主題設定必須為字串',
            'ui_theme.in' => '選擇的主題無效，請選擇 default、code 或 ocean',
        ];
    }

    /**
     * Get validated preferences array.
     *
     * @return array<string, mixed>
     */
    public function toPreferences(): array
    {
        return [
            'ui_theme' => $this->validated('ui_theme'),
        ];
    }
}

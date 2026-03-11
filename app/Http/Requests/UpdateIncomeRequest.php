<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Income\UpdateIncomeDto;
use App\Enums\CashFlowFrequencyType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate partial payload for updating an income record.
 */
class UpdateIncomeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'amount' => ['sometimes', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'frequency_type' => ['sometimes', Rule::enum(CashFlowFrequencyType::class)],
            'frequency_interval' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'note' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => '名稱不能超過 255 字',
            'amount.numeric' => '金額必須是數字',
            'amount.min' => '金額不能小於 0',
            'frequency_type.enum' => '無效的週期類型',
            'frequency_interval.min' => '間隔必須至少為 1',
            'frequency_interval.max' => '間隔不能超過 100',
            'start_date.date' => '開始日期格式不正確',
            'end_date.date' => '結束日期格式不正確',
            'end_date.after_or_equal' => '結束日期必須在開始日期之後',
            'note.max' => '備註不能超過 500 字',
        ];
    }

    /**
     * Convert validated payload into update DTO.
     */
    public function toDto(): UpdateIncomeDto
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return UpdateIncomeDto::fromArray($validated);
    }
}

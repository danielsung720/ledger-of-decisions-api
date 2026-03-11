<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RecurringExpense\GenerateRecurringExpenseDto;

/**
 * Validate payload for manual recurring-expense generation.
 */
class GenerateExpenseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date', 'before_or_equal:today'],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:10000000'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.date' => '日期格式不正確',
            'date.before_or_equal' => '日期不能是未來日期',
            'amount.numeric' => '金額必須是數字',
            'amount.min' => '金額不能小於 0',
            'amount.max' => '金額不能超過一千萬',
        ];
    }

    /**
     * Convert validated payload into manual-generate DTO.
     */
    public function toDto(): GenerateRecurringExpenseDto
    {
        return GenerateRecurringExpenseDto::fromArray($this->validated());
    }
}

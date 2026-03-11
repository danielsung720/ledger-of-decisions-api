<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RecurringExpense\RecurringExpenseHistoryQueryDto;

/**
 * Validate and normalize query filters for recurring expense history.
 */
class GetRecurringExpenseHistoryRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'limit.integer' => '筆數限制必須是整數',
            'limit.min' => '筆數限制至少為 1',
            'limit.max' => '筆數限制不可超過 100',
        ];
    }

    /**
     * @return array{limit: int}
     */
    public function filters(): array
    {
        return [
            'limit' => min(max((int) $this->input('limit', 10), 1), 100),
        ];
    }

    /**
     * Convert normalized filters to DTO.
     */
    public function toDto(): RecurringExpenseHistoryQueryDto
    {
        return RecurringExpenseHistoryQueryDto::fromArray($this->filters());
    }
}

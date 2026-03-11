<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RecurringExpense\RecurringExpenseUpcomingQueryDto;

/**
 * Validate and normalize query filters for upcoming recurring expenses.
 */
class GetUpcomingRecurringExpensesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ];
    }

    public function messages(): array
    {
        return [
            'days.integer' => '天數必須是整數',
            'days.min' => '天數至少為 1',
            'days.max' => '天數不可超過 365',
        ];
    }

    /**
     * @return array{days: int}
     */
    public function filters(): array
    {
        return [
            'days' => min(max((int) $this->input('days', 7), 1), 365),
        ];
    }

    public function toDto(int $userId): RecurringExpenseUpcomingQueryDto
    {
        return RecurringExpenseUpcomingQueryDto::forUser($userId, $this->filters()['days']);
    }
}

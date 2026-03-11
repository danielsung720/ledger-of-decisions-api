<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Expense\ExpenseFiltersDto;
use App\Enums\DatePreset;
use App\Http\Requests\Concerns\HasCashFlowFilterHelpers;
use App\Rules\CategoryList;
use App\Rules\ConfidenceLevelList;
use App\Rules\IntentList;

/**
 * Validate and normalize query filters for expense listing.
 */
class GetExpensesRequest extends ApiFormRequest
{
    use HasCashFlowFilterHelpers;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'preset' => ['nullable', 'string', 'in:' . implode(',', DatePreset::values())],
            'category' => ['nullable', new CategoryList()],
            'intent' => ['nullable', new IntentList()],
            'confidence_level' => ['nullable', new ConfidenceLevelList()],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => '開始日期格式不正確',
            'end_date.date' => '結束日期格式不正確',
            'end_date.after_or_equal' => '結束日期必須在開始日期之後',
            'preset.in' => '無效的預設時間範圍',
            'per_page.integer' => '每頁筆數必須是整數',
            'per_page.min' => '每頁筆數至少為 1',
        ];
    }

    /**
     * @return array{
     *   start_date?: string,
     *   end_date?: string,
     *   preset?: string,
     *   category?: array<string>,
     *   intent?: array<string>,
     *   confidence_level?: array<string>,
     *   per_page: int
     * }
     */
    public function filters(): array
    {
        $filters = [
            'per_page' => $this->resolvePerPage(),
        ];

        if ($this->filled('start_date')) {
            $filters['start_date'] = (string) $this->input('start_date');
        }

        if ($this->filled('end_date')) {
            $filters['end_date'] = (string) $this->input('end_date');
        }

        if ($this->filled('preset')) {
            $filters['preset'] = (string) $this->input('preset');
        }

        if ($this->has('category')) {
            $filters['category'] = $this->parseQueryArray('category');
        }

        if ($this->has('intent')) {
            $filters['intent'] = $this->parseQueryArray('intent');
        }

        if ($this->has('confidence_level')) {
            $filters['confidence_level'] = $this->parseQueryArray('confidence_level');
        }

        return $filters;
    }

    /**
     * Convert normalized filters to DTO for service/repository layer.
     */
    public function toDto(): ExpenseFiltersDto
    {
        return ExpenseFiltersDto::fromArray($this->filters());
    }
}

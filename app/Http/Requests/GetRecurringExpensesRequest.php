<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RecurringExpense\RecurringExpenseFiltersDto;
use App\Enums\Category;
use App\Enums\FrequencyType;

/**
 * Validate and normalize query filters for recurring expense listing.
 */
class GetRecurringExpensesRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $categories = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : null);

                    if ($categories === null) {
                        $fail('類別格式不正確');

                        return;
                    }

                    foreach ($categories as $category) {
                        if (!is_string($category) || Category::tryFrom(trim($category)) === null) {
                            $fail('無效的類別');

                            return;
                        }
                    }
                },
            ],
            'is_active' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (is_bool($value)) {
                        return;
                    }

                    if (is_int($value) && in_array($value, [0, 1], true)) {
                        return;
                    }

                    if (is_string($value) && in_array(strtolower($value), ['true', 'false', '1', '0'], true)) {
                        return;
                    }

                    $fail('是否啟用欄位必須是布林值');
                },
            ],
            'frequency_type' => [
                'nullable',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $types = is_array($value) ? $value : (is_string($value) ? explode(',', $value) : null);

                    if ($types === null) {
                        $fail('週期類型格式不正確');

                        return;
                    }

                    foreach ($types as $type) {
                        if (!is_string($type) || FrequencyType::tryFrom(trim($type)) === null) {
                            $fail('無效的週期類型');

                            return;
                        }
                    }
                },
            ],
            'per_page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'per_page.integer' => '每頁筆數必須是整數',
            'per_page.min' => '每頁筆數至少為 1',
        ];
    }

    /**
     * @return array{category?: array<string>, is_active?: bool, frequency_type?: array<string>, per_page: int}
     */
    public function filters(): array
    {
        $filters = [
            'per_page' => $this->resolvePerPage(),
        ];

        if ($this->has('category')) {
            $filters['category'] = $this->parseQueryArray('category');
        }

        if ($this->has('is_active')) {
            $filters['is_active'] = filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        if ($this->has('frequency_type')) {
            $filters['frequency_type'] = $this->parseQueryArray('frequency_type');
        }

        return $filters;
    }

    /**
     * Convert normalized filters to DTO for service/repository layer.
     */
    public function toDto(): RecurringExpenseFiltersDto
    {
        return RecurringExpenseFiltersDto::fromArray($this->filters());
    }

    /**
     * @return array<string>
     */
    private function parseQueryArray(string $key): array
    {
        $rawValue = $this->input($key);

        if (is_array($rawValue)) {
            return array_values(array_filter(
                array_map(static fn (mixed $value): string => trim((string) $value), $rawValue),
                static fn (string $value): bool => $value !== ''
            ));
        }

        if (!is_string($rawValue)) {
            return [];
        }

        return array_values(array_filter(
            array_map('trim', explode(',', $rawValue)),
            static fn (string $value): bool => $value !== ''
        ));
    }

    private function resolvePerPage(): int
    {
        return min(max((int) $this->input('per_page', 15), 1), 100);
    }
}

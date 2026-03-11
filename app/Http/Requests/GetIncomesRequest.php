<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Income\IncomeFiltersDto;
use App\Http\Requests\Concerns\HasCashFlowFilterHelpers;
use App\Rules\FlexibleBoolean;
use App\Rules\FrequencyTypeList;

/**
 * Validate and normalize query filters for income listing.
 */
class GetIncomesRequest extends ApiFormRequest
{
    use HasCashFlowFilterHelpers;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'is_active' => ['nullable', new FlexibleBoolean()],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'frequency_type' => ['nullable', new FrequencyTypeList()],
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
     * @return array{
     *   is_active?: bool,
     *   frequency_type?: array<string>,
     *   per_page: int
     * }
     */
    public function filters(): array
    {
        $filters = [
            'per_page' => $this->resolvePerPage(),
        ];

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
    public function toDto(): IncomeFiltersDto
    {
        return IncomeFiltersDto::fromArray($this->filters());
    }
}

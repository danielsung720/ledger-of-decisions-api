<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\CashFlowItem\CashFlowItemFiltersDto;
use App\Http\Requests\Concerns\HasCashFlowFilterHelpers;
use App\Rules\CategoryList;
use App\Rules\FlexibleBoolean;
use App\Rules\FrequencyTypeList;

/**
 * Validate and normalize query filters for cash flow item listing.
 */
class GetCashFlowItemsRequest extends ApiFormRequest
{
    use HasCashFlowFilterHelpers;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category' => ['nullable', new CategoryList()],
            'is_active' => ['nullable', new FlexibleBoolean()],
            'frequency_type' => ['nullable', new FrequencyTypeList()],
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
     * @return array{
     *   category?: array<string>,
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
    public function toDto(): CashFlowItemFiltersDto
    {
        return CashFlowItemFiltersDto::fromArray($this->filters());
    }
}

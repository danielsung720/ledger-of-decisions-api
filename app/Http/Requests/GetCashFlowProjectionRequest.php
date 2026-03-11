<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\CashFlow\CashFlowProjectionFiltersDto;

/**
 * Validate and normalize query filters for cash flow projection endpoint.
 */
class GetCashFlowProjectionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'months' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'months.integer' => '預測月數必須是整數',
            'months.min' => '預測月數至少為 1',
        ];
    }

    /**
     * @return array{months: int}
     */
    public function filters(): array
    {
        return [
            'months' => min(max((int) $this->input('months', 1), 1), 12),
        ];
    }

    /**
     * Convert normalized filters into projection DTO.
     */
    public function toDto(): CashFlowProjectionFiltersDto
    {
        return CashFlowProjectionFiltersDto::fromArray($this->filters());
    }
}

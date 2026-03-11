<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Statistics\StatisticsFilterDto;
use App\Enums\DatePreset;
use Illuminate\Validation\Rule;

/**
 * Validate query filters for intent and summary statistics endpoints.
 */
class GetIntentsRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'start_date' => ['nullable', 'date', 'before_or_equal:end_date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'preset' => ['nullable', Rule::enum(DatePreset::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'start_date.date' => '起始日期格式不正確',
            'start_date.before_or_equal' => '起始日期不可晚於結束日期',
            'end_date.date' => '結束日期格式不正確',
            'end_date.after_or_equal' => '結束日期不可早於起始日期',
            'preset.enum' => '預設範圍僅支援 today、this_week、this_month',
        ];
    }

    /**
     * Convert validated query string into a normalized statistics filter DTO.
     */
    public function toDto(): StatisticsFilterDto
    {
        /** @var array{start_date?: string, end_date?: string, preset?: string} $validated */
        $validated = $this->validated();

        return StatisticsFilterDto::fromArray($validated);
    }
}

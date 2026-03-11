<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\RecurringExpense\CreateRecurringExpenseDto;
use App\Enums\Category;
use App\Enums\FrequencyType;
use App\Enums\Intent;
use Illuminate\Validation\Rule;

/**
 * Validate payload for creating a recurring expense.
 */
class StoreRecurringExpenseRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'amount_min' => ['required', 'numeric', 'min:0'],
            'amount_max' => ['nullable', 'numeric', 'min:0', 'gte:amount_min'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'category' => ['required', Rule::enum(Category::class)],
            'frequency_type' => ['required', Rule::enum(FrequencyType::class)],
            'frequency_interval' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'day_of_month' => ['nullable', 'integer', 'min:1', 'max:31'],
            'month_of_year' => ['nullable', 'integer', 'min:1', 'max:12'],
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'default_intent' => ['nullable', Rule::enum(Intent::class)],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => '請輸入固定支出名稱',
            'name.max' => '名稱不能超過 255 字',
            'amount_min.required' => '請輸入金額',
            'amount_min.numeric' => '金額必須是數字',
            'amount_min.min' => '金額不能小於 0',
            'amount_max.numeric' => '最大金額必須是數字',
            'amount_max.min' => '最大金額不能小於 0',
            'amount_max.gte' => '最大金額必須大於或等於最小金額',
            'category.required' => '請選擇消費類別',
            'category.enum' => '無效的消費類別',
            'frequency_type.required' => '請選擇週期類型',
            'frequency_type.enum' => '無效的週期類型',
            'frequency_interval.min' => '間隔必須至少為 1',
            'frequency_interval.max' => '間隔不能超過 100',
            'day_of_month.min' => '日期必須在 1-31 之間',
            'day_of_month.max' => '日期必須在 1-31 之間',
            'month_of_year.min' => '月份必須在 1-12 之間',
            'month_of_year.max' => '月份必須在 1-12 之間',
            'day_of_week.min' => '星期必須在 0-6 之間',
            'day_of_week.max' => '星期必須在 0-6 之間',
            'start_date.required' => '請選擇開始日期',
            'start_date.date' => '開始日期格式不正確',
            'end_date.date' => '結束日期格式不正確',
            'end_date.after_or_equal' => '結束日期必須在開始日期之後',
            'default_intent.enum' => '無效的決策意圖',
            'note.max' => '備註不能超過 500 字',
        ];
    }

    /**
     * Convert validated payload into create DTO.
     */
    public function toDto(): CreateRecurringExpenseDto
    {
        return CreateRecurringExpenseDto::fromArray($this->validated());
    }
}

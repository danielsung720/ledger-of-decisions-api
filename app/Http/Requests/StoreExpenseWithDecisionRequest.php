<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Entry\CreateEntryDto;
use App\Enums\Category;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate payload for creating expense and decision in one request.
 */
class StoreExpenseWithDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Expense fields
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'category' => ['required', Rule::enum(Category::class)],
            'occurred_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],

            // Decision fields
            'intent' => ['required', Rule::enum(Intent::class)],
            'confidence_level' => ['sometimes', Rule::enum(ConfidenceLevel::class)],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => '請輸入消費金額',
            'amount.numeric' => '金額必須是數字',
            'amount.min' => '金額不能小於 0',
            'category.required' => '請選擇消費類別',
            'category.enum' => '無效的消費類別',
            'occurred_at.required' => '請輸入消費時間',
            'occurred_at.date' => '消費時間格式不正確',
            'note.max' => '備註不能超過 500 字',
            'intent.required' => '請選擇決策意圖',
            'intent.enum' => '無效的決策意圖',
            'confidence_level.enum' => '無效的信心程度',
            'decision_note.max' => '決策備註不能超過 1000 字',
        ];
    }

    /**
     * Convert validated payload into entry DTO.
     */
    public function toDto(): CreateEntryDto
    {
        /**
         * @var array{
         *   amount: int|float|string,
         *   currency?: string,
         *   category: string,
         *   occurred_at: string,
         *   note?: string|null,
         *   intent: string,
         *   confidence_level?: string,
         *   decision_note?: string|null
         * } $validated
         */
        $validated = $this->validated();

        return CreateEntryDto::fromArray($validated);
    }
}

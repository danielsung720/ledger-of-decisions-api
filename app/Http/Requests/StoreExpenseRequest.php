<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Expense\CreateExpenseDto;
use App\Enums\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validate payload for creating an expense.
 */
class StoreExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3'],
            'category' => ['required', Rule::enum(Category::class)],
            'occurred_at' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
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
        ];
    }

    /**
     * Convert validated payload into create DTO.
     */
    public function toDto(): CreateExpenseDto
    {
        /** @var array{amount: int|float|string, currency?: string, category: string, occurred_at: string, note?: string|null} $validated */
        $validated = $this->validated();

        return CreateExpenseDto::fromArray($validated);
    }
}

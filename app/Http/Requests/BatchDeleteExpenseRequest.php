<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Expense\BatchDeleteExpenseDto;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validate payload for batch deleting expenses.
 */
class BatchDeleteExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:100'],
            'ids.*' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'ids 欄位為必填',
            'ids.array' => 'ids 欄位必須是陣列',
            'ids.min' => '請至少選擇一筆要刪除的紀錄',
            'ids.max' => '單次最多只能刪除 100 筆紀錄',
            'ids.*.required' => 'ID 不能為空',
            'ids.*.integer' => 'ID 必須是整數',
            'ids.*.min' => 'ID 必須是正整數',
        ];
    }

    /**
     * Convert validated payload into batch delete DTO.
     */
    public function toDto(): BatchDeleteExpenseDto
    {
        /** @var array{ids: array<int>} $validated */
        $validated = $this->validated();

        return BatchDeleteExpenseDto::fromArray($validated);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTO\Decision\CreateDecisionDto;
use App\DTO\Decision\UpdateDecisionDto;
use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use Illuminate\Validation\Rule;

/**
 * Validate payload for creating or updating an expense decision.
 */
class StoreDecisionRequest extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'intent' => ['required', Rule::enum(Intent::class)],
            'confidence_level' => ['nullable', Rule::enum(ConfidenceLevel::class)],
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'intent.required' => '請選擇決策意圖',
            'intent.enum' => '無效的決策意圖',
            'confidence_level.enum' => '無效的信心程度',
            'decision_note.max' => '決策備註不能超過 1000 字',
        ];
    }

    /**
     * Convert validated payload into create DTO.
     */
    public function toCreateDto(): CreateDecisionDto
    {
        return CreateDecisionDto::fromArray($this->validated());
    }

    /**
     * Convert validated payload into update DTO.
     */
    public function toUpdateDto(): UpdateDecisionDto
    {
        return UpdateDecisionDto::fromArray($this->validated());
    }
}

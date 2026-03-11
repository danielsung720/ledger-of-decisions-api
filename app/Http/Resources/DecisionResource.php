<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Decision
 *
 * API resource transformer for a single decision record.
 */
class DecisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'expense_id' => $this->expense_id,
            'intent' => $this->intent->value,
            'intent_label' => $this->intent->label(),
            'confidence_level' => $this->confidence_level?->value,
            'confidence_level_label' => $this->confidence_level?->label(),
            'decision_note' => $this->decision_note,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Expense
 *
 * API resource transformer for expense record with decision context.
 */
class ExpenseWithDecisionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'occurred_at' => $this->occurred_at->toIso8601String(),
            'note' => $this->note,
            'decision' => $this->whenLoaded('decision', function () {
                return [
                    'id' => $this->decision->id,
                    'intent' => $this->decision->intent->value,
                    'intent_label' => $this->decision->intent->label(),
                    'confidence_level' => $this->decision->confidence_level->value,
                    'confidence_level_label' => $this->decision->confidence_level->label(),
                    'decision_note' => $this->decision->decision_note,
                ];
            }),
            'recurring_expense_id' => $this->recurring_expense_id,
            'is_from_recurring' => $this->isFromRecurring(),
            'recurring_expense' => $this->whenLoaded('recurringExpense', function () {
                return [
                    'id' => $this->recurringExpense->id,
                    'name' => $this->recurringExpense->name,
                ];
            }),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\RecurringExpense
 *
 * API resource transformer for a single recurring expense.
 */
class RecurringExpenseResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount_min' => $this->amount_min,
            'amount_max' => $this->amount_max,
            'amount_display' => $this->formatAmountDisplay(),
            'currency' => $this->currency,
            'category' => $this->category->value,
            'category_label' => $this->category->label(),
            'frequency_type' => $this->frequency_type->value,
            'frequency_type_label' => $this->frequency_type->label(),
            'frequency_interval' => $this->frequency_interval,
            'frequency_display' => $this->formatFrequencyDisplay(),
            'day_of_month' => $this->day_of_month,
            'month_of_year' => $this->month_of_year,
            'day_of_week' => $this->day_of_week,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'next_occurrence' => $this->next_occurrence?->toDateString(),
            'default_intent' => $this->default_intent?->value,
            'default_intent_label' => $this->default_intent?->label(),
            'note' => $this->note,
            'is_active' => $this->is_active,
            'has_amount_range' => $this->hasAmountRange(),
            'expenses_count' => $this->whenCounted('expenses'),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Render fixed or ranged amount for display usage in UI.
     */
    private function formatAmountDisplay(): string
    {
        if ($this->hasAmountRange()) {
            return "\${$this->amount_min} ~ \${$this->amount_max}";
        }

        return "\${$this->amount_min}";
    }

    /**
     * Render localized frequency label with interval context.
     */
    private function formatFrequencyDisplay(): string
    {
        $interval = $this->frequency_interval;

        if ($interval === 1) {
            return $this->frequency_type->label();
        }

        return match ($this->frequency_type->value) {
            'daily' => "每 {$interval} 天",
            'weekly' => "每 {$interval} 週",
            'monthly' => "每 {$interval} 月",
            'yearly' => "每 {$interval} 年",
        };
    }
}

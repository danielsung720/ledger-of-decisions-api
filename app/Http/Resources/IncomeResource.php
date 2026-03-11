<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Income
 *
 * API resource transformer for a single income record.
 */
class IncomeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'amount_display' => $this->formatAmountDisplay(),
            'currency' => $this->currency,
            'frequency_type' => $this->frequency_type->value,
            'frequency_type_label' => $this->frequency_type->label(),
            'frequency_interval' => $this->frequency_interval,
            'frequency_display' => $this->formatFrequencyDisplay(),
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'note' => $this->note,
            'is_active' => $this->is_active,
            'monthly_amount' => $this->getMonthlyAmount(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Render amount for display usage in UI.
     */
    private function formatAmountDisplay(): string
    {
        return "\${$this->amount}";
    }

    /**
     * Render localized frequency label with interval context.
     */
    private function formatFrequencyDisplay(): string
    {
        $interval = $this->frequency_interval;

        if ($this->frequency_type->value === 'one_time') {
            return '一次性';
        }

        if ($interval === 1) {
            return $this->frequency_type->label();
        }

        return match ($this->frequency_type->value) {
            'monthly' => "每 {$interval} 月",
            // @phpstan-ignore match.alwaysTrue (defensive fallback for future enum cases)
            'yearly' => "每 {$interval} 年",
            default => $this->frequency_type->label(),
        };
    }
}

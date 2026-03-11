<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CashFlowFrequencyType;
use App\Enums\Category;
use App\Models\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $amount
 * @property string $currency
 * @property Category $category
 * @property CashFlowFrequencyType $frequency_type
 * @property int $frequency_interval
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property string|null $note
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder|static active()
 * @method static Builder|static inCategory(string|array $categories)
 * @method static Builder|static validForPeriod(Carbon $start, Carbon $end)
 */
class CashFlowItem extends Model
{
    use BelongsToUser;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount',
        'currency',
        'category',
        'frequency_type',
        'frequency_interval',
        'start_date',
        'end_date',
        'note',
        'is_active',
    ];

    protected $attributes = [
        'currency' => 'TWD',
        'frequency_interval' => 1,
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'category' => Category::class,
            'frequency_type' => CashFlowFrequencyType::class,
            'frequency_interval' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Calculate the monthly equivalent amount.
     */
    public function getMonthlyAmount(): string
    {
        return $this->frequency_type->toMonthlyAmount(
            $this->amount,
            $this->frequency_interval
        );
    }

    /**
     * Check if this item is active for a given month.
     */
    public function isActiveForMonth(Carbon $month): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        // Check if start_date is before or during this month
        if ($this->start_date->gt($monthEnd)) {
            return false;
        }

        // Check if end_date is after or during this month
        if ($this->end_date && $this->end_date->lt($monthStart)) {
            return false;
        }

        return true;
    }

    /**
     * Get the amount for a specific month (handles one-time payments).
     */
    public function getAmountForMonth(Carbon $month): string
    {
        if (!$this->isActiveForMonth($month)) {
            return '0.00';
        }

        if ($this->frequency_type === CashFlowFrequencyType::OneTime) {
            // One-time payment only applies in the start month
            if ($this->start_date->isSameMonth($month)) {
                return $this->amount;
            }
            return '0.00';
        }

        if ($this->frequency_type === CashFlowFrequencyType::Yearly) {
            // Yearly payment applies in the month of start_date
            if ($this->start_date->month === $month->month) {
                return $this->divideAmountByInterval();
            }
            return '0.00';
        }

        // Monthly: divide by interval
        return $this->divideAmountByInterval();
    }

    private function divideAmountByInterval(int $scale = 2): string
    {
        $interval = max($this->frequency_interval, 1);

        if (function_exists('bcdiv')) {
            $raw = bcdiv($this->amount, (string) $interval, $scale + 1);
            $rounding = '0.' . str_repeat('0', $scale) . '5';

            return bcadd($raw, $rounding, $scale);
        }

        return number_format((float) $this->amount / $interval, $scale, '.', '');
    }

    /**
     * Scope to get active items.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by category.
     *
     * @param Builder $query
     * @param string|array<string> $categories
     */
    public function scopeInCategory(Builder $query, string|array $categories): Builder
    {
        if (is_array($categories)) {
            return $query->whereIn('category', $categories);
        }
        return $query->where('category', $categories);
    }

    /**
     * Scope to get items valid for a given date range.
     */
    public function scopeValidForPeriod(Builder $query, Carbon $start, Carbon $end): Builder
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', $end)
            ->where(function ($q) use ($start) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $start);
            });
    }
}

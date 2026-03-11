<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Category;
use App\Enums\FrequencyType;
use App\Enums\Intent;
use App\Models\Traits\BelongsToUser;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $amount_min
 * @property string|null $amount_max
 * @property string $currency
 * @property Category $category
 * @property FrequencyType $frequency_type
 * @property int $frequency_interval
 * @property int|null $day_of_month
 * @property int|null $month_of_year
 * @property int|null $day_of_week
 * @property \Illuminate\Support\Carbon $start_date
 * @property \Illuminate\Support\Carbon|null $end_date
 * @property \Carbon\Carbon|null $next_occurrence
 * @property Intent|null $default_intent
 * @property string|null $note
 * @property bool $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static Builder|static active()
 * @method static Builder|static due(?Carbon $date = null)
 * @method static Builder|static upcoming(int $days = 7)
 */
class RecurringExpense extends Model
{
    use BelongsToUser;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'amount_min',
        'amount_max',
        'currency',
        'category',
        'frequency_type',
        'frequency_interval',
        'day_of_month',
        'month_of_year',
        'day_of_week',
        'start_date',
        'end_date',
        'next_occurrence',
        'default_intent',
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
            'amount_min' => 'decimal:2',
            'amount_max' => 'decimal:2',
            'category' => Category::class,
            'frequency_type' => FrequencyType::class,
            'frequency_interval' => 'integer',
            'day_of_month' => 'integer',
            'month_of_year' => 'integer',
            'day_of_week' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'next_occurrence' => 'date',
            'default_intent' => Intent::class,
            'is_active' => 'boolean',
        ];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    /**
     * Check if this recurring expense has a variable amount range.
     */
    public function hasAmountRange(): bool
    {
        return $this->amount_max !== null && $this->amount_max != $this->amount_min;
    }

    /**
     * Generate an amount for this recurring expense.
     * If amount_max is set, returns a random value in the range.
     */
    public function generateAmount(): string
    {
        if (!$this->hasAmountRange()) {
            return $this->amount_min;
        }

        $min = (float) $this->amount_min;
        $max = (float) $this->amount_max;
        $amount = $min + (mt_rand() / mt_getrandmax()) * ($max - $min);

        return number_format($amount, 2, '.', '');
    }

    /**
     * Check if this recurring expense is due on or before the given date.
     */
    public function isDue(?Carbon $date = null): bool
    {
        $date = $date ?? Carbon::today();

        if (!$this->is_active) {
            return false;
        }

        if ($this->end_date && $date->gt($this->end_date)) {
            return false;
        }

        return $this->next_occurrence && $date->gte($this->next_occurrence);
    }

    /**
     * Calculate the next occurrence date from a given date.
     */
    public function calculateNextOccurrence(?Carbon $fromDate = null): ?Carbon
    {
        $fromDate = $fromDate ?? Carbon::today();

        if ($this->end_date && $fromDate->gt($this->end_date)) {
            return null;
        }

        return match ($this->frequency_type) {
            FrequencyType::Daily => $this->calculateNextDaily($fromDate),
            FrequencyType::Weekly => $this->calculateNextWeekly($fromDate),
            FrequencyType::Monthly => $this->calculateNextMonthly($fromDate),
            FrequencyType::Yearly => $this->calculateNextYearly($fromDate),
        };
    }

    /**
     * Calculate next occurrence for daily frequency.
     */
    private function calculateNextDaily(Carbon $fromDate): Carbon
    {
        return $fromDate->copy()->addDays($this->frequency_interval);
    }

    /**
     * Calculate next occurrence for weekly frequency.
     */
    private function calculateNextWeekly(Carbon $fromDate): Carbon
    {
        $next = $fromDate->copy();

        if ($this->day_of_week !== null) {
            $next = $next->next($this->day_of_week);
            if ($this->frequency_interval > 1) {
                $next->addWeeks($this->frequency_interval - 1);
            }
        } else {
            $next->addWeeks($this->frequency_interval);
        }

        return $next;
    }

    /**
     * Calculate next occurrence for monthly frequency.
     * Handles month-end edge cases (e.g., 31st in February becomes 28th/29th).
     */
    private function calculateNextMonthly(Carbon $fromDate): Carbon
    {
        $next = $fromDate->copy()->addMonths($this->frequency_interval);

        if ($this->day_of_month !== null) {
            $targetDay = $this->day_of_month;
            $daysInMonth = $next->daysInMonth;
            $actualDay = min($targetDay, $daysInMonth);
            $next->day($actualDay);
        }

        return $next;
    }

    /**
     * Calculate next occurrence for yearly frequency.
     * Handles leap year edge cases (Feb 29 becomes Feb 28 in non-leap years).
     */
    private function calculateNextYearly(Carbon $fromDate): Carbon
    {
        $next = $fromDate->copy()->addYears($this->frequency_interval);

        if ($this->month_of_year !== null) {
            $next->month($this->month_of_year);

            if ($this->day_of_month !== null) {
                $daysInMonth = $next->daysInMonth;
                $actualDay = min($this->day_of_month, $daysInMonth);
                $next->day($actualDay);
            }
        }

        return $next;
    }

    /**
     * Update next_occurrence after generating an expense.
     */
    public function advanceNextOccurrence(): void
    {
        $next = $this->calculateNextOccurrence($this->next_occurrence);

        if ($next === null || ($this->end_date && $next->gt($this->end_date))) {
            // Deactivate but keep the last next_occurrence value (DB doesn't allow null)
            $this->is_active = false;
        } else {
            $this->next_occurrence = $next;
        }

        $this->save();
    }

    /**
     * Get all missed occurrences between last execution and now.
     */
    public function getMissedOccurrences(?Carbon $upToDate = null): array
    {
        $upToDate = $upToDate ?? Carbon::today();
        $occurrences = [];

        if (!$this->next_occurrence || !$this->is_active) {
            return $occurrences;
        }

        $current = $this->next_occurrence->copy();

        while ($current->lte($upToDate)) {
            if ($this->end_date && $current->gt($this->end_date)) {
                break;
            }

            $occurrences[] = $current->copy();
            $next = $this->calculateNextOccurrence($current);

            if ($next === null || $next->lte($current)) {
                break;
            }

            $current = $next;
        }

        return $occurrences;
    }

    /**
     * Scope to get active recurring expenses.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get recurring expenses due on or before a date.
     */
    public function scopeDue($query, ?Carbon $date = null)
    {
        $date = $date ?? Carbon::today();

        return $query->active()
            ->where('next_occurrence', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            });
    }

    /**
     * Scope to get upcoming recurring expenses within N days.
     */
    public function scopeUpcoming($query, int $days = 7)
    {
        $today = Carbon::today();
        $endDate = $today->copy()->addDays($days);

        return $query->active()
            ->whereBetween('next_occurrence', [$today, $endDate])
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $today);
            });
    }
}

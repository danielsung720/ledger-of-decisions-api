<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Category;
use App\Models\Traits\BelongsToUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $amount
 * @property string $currency
 * @property Category $category
 * @property \Illuminate\Support\Carbon $occurred_at
 * @property string|null $note
 * @property int|null $recurring_expense_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Decision|null $decision
 * @property-read RecurringExpense|null $recurringExpense
 */
class Expense extends Model
{
    use BelongsToUser;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'currency',
        'category',
        'occurred_at',
        'note',
        'recurring_expense_id',
    ];

    protected $attributes = [
        'currency' => 'TWD',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'category' => Category::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function decision(): HasOne
    {
        return $this->hasOne(Decision::class);
    }

    public function recurringExpense(): BelongsTo
    {
        return $this->belongsTo(RecurringExpense::class);
    }

    /**
     * Check if this expense was generated from a recurring expense.
     */
    public function isFromRecurring(): bool
    {
        return $this->recurring_expense_id !== null;
    }
}

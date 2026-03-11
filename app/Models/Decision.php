<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $expense_id
 * @property Intent $intent
 * @property ConfidenceLevel|null $confidence_level
 * @property string|null $decision_note
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Decision extends Model
{
    use HasFactory;

    protected $fillable = [
        'expense_id',
        'intent',
        'confidence_level',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'intent' => Intent::class,
            'confidence_level' => ConfidenceLevel::class,
        ];
    }

    public function expense(): BelongsTo
    {
        return $this->belongsTo(Expense::class);
    }
}

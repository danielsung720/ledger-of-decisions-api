<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Income;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an income is updated.
 */
class IncomeUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Income $income
    ) {
    }
}

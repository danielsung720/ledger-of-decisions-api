<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\Expense;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired after an expense and decision are created together.
 */
class ExpenseWithDecisionCreated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Expense $expense
    ) {
    }
}

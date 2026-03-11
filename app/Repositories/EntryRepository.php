<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Entry\CreateEntryDto;
use App\Models\Expense;
use Illuminate\Support\Facades\DB;

/**
 * Persistence operations for combined expense + decision creation.
 */
class EntryRepository
{
    /**
     * Create expense and decision atomically, then load decision relation.
     */
    public function createWithDecision(CreateEntryDto $payload): Expense
    {
        $expense = DB::transaction(function () use ($payload): Expense {
            $expense = Expense::create($payload->expense->toArray());

            $expense->decision()->create($payload->decision->toArray());

            return $expense;
        });

        return $expense->load('decision');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Entry\CreateEntryDto;
use App\Events\ExpenseWithDecisionCreated;
use App\Models\Expense;
use App\Repositories\EntryRepository;

/**
 * Application service for combined expense-entry workflows.
 */
class EntryService
{
    public function __construct(
        private readonly EntryRepository $entryRepository
    ) {
    }

    public function create(CreateEntryDto $payload): Expense
    {
        $expense = $this->entryRepository->createWithDecision($payload);
        event(new ExpenseWithDecisionCreated($expense));

        return $expense;
    }
}

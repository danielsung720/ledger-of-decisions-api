<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Income\CreateIncomeDto;
use App\DTO\Income\IncomePaginateQueryDto;
use App\DTO\Income\UpdateIncomeDto;
use App\Events\IncomeCreated;
use App\Events\IncomeDeleted;
use App\Events\IncomeUpdated;
use App\Models\Income;
use App\Repositories\IncomeRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Application service for income use-cases.
 */
class IncomeService
{
    public function __construct(
        private readonly IncomeRepository $incomeRepository
    ) {
    }

    /**
     * Paginate incomes by scope and filters.
     */
    public function paginate(IncomePaginateQueryDto $query): LengthAwarePaginator
    {
        return $this->incomeRepository->paginate($query);
    }

    /**
     * Create a new income.
     */
    public function create(CreateIncomeDto $payload): Income
    {
        $income = $this->incomeRepository->create($payload);
        event(new IncomeCreated($income));

        return $income;
    }

    /**
     * Update an existing income.
     */
    public function update(Income $income, UpdateIncomeDto $payload): Income
    {
        $updatedIncome = $this->incomeRepository->update($income, $payload);
        event(new IncomeUpdated($updatedIncome));

        return $updatedIncome;
    }

    /**
     * Delete an income.
     */
    public function delete(Income $income): void
    {
        $this->incomeRepository->delete($income);
        event(new IncomeDeleted($income));
    }
}

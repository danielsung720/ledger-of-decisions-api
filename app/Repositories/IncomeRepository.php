<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\Income\CreateIncomeDto;
use App\DTO\Income\IncomePaginateQueryDto;
use App\DTO\Income\UpdateIncomeDto;
use App\Models\Income;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Persistence operations for incomes.
 */
class IncomeRepository
{
    /**
     * Paginate incomes using access scope and filter criteria.
     */
    public function paginate(IncomePaginateQueryDto $queryDto): LengthAwarePaginator
    {
        $query = Income::query()->whereIn('user_id', $queryDto->scope->userIds());

        if ($queryDto->filters->isActive !== null) {
            $query->withActiveStatus($queryDto->filters->isActive);
        }

        if ($queryDto->filters->frequencyTypes !== []) {
            $query->withFrequencyTypes($queryDto->filters->frequencyTypes);
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($queryDto->filters->perPage);
    }

    /**
     * Create a new income row.
     */
    public function create(CreateIncomeDto $payload): Income
    {
        return Income::create($payload->toArray());
    }

    /**
     * Update a persisted income row.
     */
    public function update(Income $income, UpdateIncomeDto $payload): Income
    {
        $income->update($payload->toArray());

        return $income;
    }

    /**
     * Delete an income row.
     */
    public function delete(Income $income): void
    {
        $income->delete();
    }
}

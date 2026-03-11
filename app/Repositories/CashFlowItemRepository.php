<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTO\CashFlowItem\CashFlowItemPaginateQueryDto;
use App\DTO\CashFlowItem\CreateCashFlowItemDto;
use App\DTO\CashFlowItem\UpdateCashFlowItemDto;
use App\Models\CashFlowItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Persistence operations for cash flow items.
 */
class CashFlowItemRepository
{
    /**
     * Paginate cash flow items by scope and filters.
     */
    public function paginate(CashFlowItemPaginateQueryDto $queryDto): LengthAwarePaginator
    {
        $query = CashFlowItem::query()->whereIn('user_id', $queryDto->scope->userIds());

        if ($queryDto->filters->categories !== []) {
            $query->inCategory($queryDto->filters->categories);
        }

        if ($queryDto->filters->isActive !== null) {
            $query->where('is_active', $queryDto->filters->isActive);
        }

        if ($queryDto->filters->frequencyTypes !== []) {
            $query->whereIn('frequency_type', $queryDto->filters->frequencyTypes);
        }

        return $query->orderBy('created_at', 'desc')->paginate($queryDto->filters->perPage);
    }

    /**
     * Create a new cash flow item row.
     */
    public function create(CreateCashFlowItemDto $payload): CashFlowItem
    {
        return CashFlowItem::create($payload->toArray());
    }

    /**
     * Update a persisted cash flow item row.
     */
    public function update(CashFlowItem $cashFlowItem, UpdateCashFlowItemDto $payload): CashFlowItem
    {
        $cashFlowItem->update($payload->toArray());

        return $cashFlowItem;
    }

    /**
     * Delete a cash flow item row.
     */
    public function delete(CashFlowItem $cashFlowItem): void
    {
        $cashFlowItem->delete();
    }
}

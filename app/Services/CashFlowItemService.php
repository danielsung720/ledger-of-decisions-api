<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\CashFlowItem\CashFlowItemPaginateQueryDto;
use App\DTO\CashFlowItem\CreateCashFlowItemDto;
use App\DTO\CashFlowItem\UpdateCashFlowItemDto;
use App\Events\CashFlowItemCreated;
use App\Events\CashFlowItemDeleted;
use App\Events\CashFlowItemUpdated;
use App\Models\CashFlowItem;
use App\Repositories\CashFlowItemRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Application service for cash flow item use-cases.
 */
class CashFlowItemService
{
    public function __construct(
        private readonly CashFlowItemRepository $cashFlowItemRepository
    ) {
    }

    public function paginate(CashFlowItemPaginateQueryDto $query): LengthAwarePaginator
    {
        return $this->cashFlowItemRepository->paginate($query);
    }

    /**
     * Create a new cash flow item.
     */
    public function create(CreateCashFlowItemDto $payload): CashFlowItem
    {
        $cashFlowItem = $this->cashFlowItemRepository->create($payload);
        event(new CashFlowItemCreated($cashFlowItem));

        return $cashFlowItem;
    }

    /**
     * Retrieve one cash flow item.
     */
    public function show(CashFlowItem $cashFlowItem): CashFlowItem
    {
        return $cashFlowItem;
    }

    /**
     * Update one cash flow item.
     */
    public function update(CashFlowItem $cashFlowItem, UpdateCashFlowItemDto $payload): CashFlowItem
    {
        $updatedCashFlowItem = $this->cashFlowItemRepository->update($cashFlowItem, $payload);
        event(new CashFlowItemUpdated($updatedCashFlowItem));

        return $updatedCashFlowItem;
    }

    /**
     * Delete one cash flow item.
     */
    public function delete(CashFlowItem $cashFlowItem): void
    {
        $this->cashFlowItemRepository->delete($cashFlowItem);
        event(new CashFlowItemDeleted($cashFlowItem));
    }
}

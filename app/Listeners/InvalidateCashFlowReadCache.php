<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CacheDomainEnum;
use App\Events\CashFlowItemCreated;
use App\Events\CashFlowItemDeleted;
use App\Events\CashFlowItemUpdated;
use App\Events\IncomeCreated;
use App\Events\IncomeDeleted;
use App\Events\IncomeUpdated;
use App\Services\ApiReadCacheService;

class InvalidateCashFlowReadCache
{
    public function __construct(
        private readonly ApiReadCacheService $apiReadCacheService
    ) {
    }

    public function handle(
        IncomeCreated|IncomeUpdated|IncomeDeleted|CashFlowItemCreated|CashFlowItemUpdated|CashFlowItemDeleted $event
    ): void {
        $userId = match (true) {
            $event instanceof IncomeCreated,
            $event instanceof IncomeUpdated,
            $event instanceof IncomeDeleted => (int) $event->income->user_id,
            default => (int) $event->cashFlowItem->user_id,
        };

        $this->apiReadCacheService->invalidateDomainVersion($userId, CacheDomainEnum::CashFlow);
    }
}

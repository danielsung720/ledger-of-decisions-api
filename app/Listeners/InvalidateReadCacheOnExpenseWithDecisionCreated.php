<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Enums\CacheDomainEnum;
use App\Events\ExpenseWithDecisionCreated;
use App\Services\ApiReadCacheService;

class InvalidateReadCacheOnExpenseWithDecisionCreated
{
    public function __construct(
        private readonly ApiReadCacheService $apiReadCacheService
    ) {
    }

    public function handle(ExpenseWithDecisionCreated $event): void
    {
        $userId = (int) $event->expense->user_id;
        $this->apiReadCacheService->invalidateDomainVersion($userId, CacheDomainEnum::Statistics);
        $this->apiReadCacheService->invalidateDomainVersion($userId, CacheDomainEnum::Expenses);
    }
}

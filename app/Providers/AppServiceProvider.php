<?php

namespace App\Providers;

use App\Events\CashFlowItemCreated;
use App\Events\CashFlowItemDeleted;
use App\Events\CashFlowItemUpdated;
use App\Events\ExpenseWithDecisionCreated;
use App\Events\IncomeCreated;
use App\Events\IncomeDeleted;
use App\Events\IncomeUpdated;
use App\Listeners\InvalidateCashFlowReadCache;
use App\Listeners\InvalidateReadCacheOnExpenseWithDecisionCreated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(
            [
                IncomeCreated::class,
                IncomeUpdated::class,
                IncomeDeleted::class,
                CashFlowItemCreated::class,
                CashFlowItemUpdated::class,
                CashFlowItemDeleted::class,
            ],
            InvalidateCashFlowReadCache::class
        );

        Event::listen(
            ExpenseWithDecisionCreated::class,
            InvalidateReadCacheOnExpenseWithDecisionCreated::class
        );
    }
}

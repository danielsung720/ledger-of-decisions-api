<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\CashFlowItem;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CashFlowItemUpdated
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public CashFlowItem $cashFlowItem)
    {
    }
}

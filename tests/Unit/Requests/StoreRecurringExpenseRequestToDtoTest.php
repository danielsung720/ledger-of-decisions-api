<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreRecurringExpenseRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreRecurringExpenseRequestToDtoTest extends TestCase
{
    #[Test]
    public function ToDtoShouldMapPayloadAndSetNextOccurrence(): void
    {
        $request = StoreRecurringExpenseRequest::create('/api/recurring-expenses', 'POST', [
            'name' => '房租',
            'amount_min' => 15000,
            'amount_max' => 20000,
            'category' => 'living',
            'frequency_type' => 'monthly',
            'start_date' => '2026-02-01',
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertSame('房租', $dto->name);
        $this->assertSame(15000.0, $dto->amountMin);
        $this->assertSame(20000.0, $dto->amountMax);
        $this->assertSame('2026-02-01', $dto->nextOccurrence);
    }
}

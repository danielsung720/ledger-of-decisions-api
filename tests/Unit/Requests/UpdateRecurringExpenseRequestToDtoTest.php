<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\UpdateRecurringExpenseRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateRecurringExpenseRequestToDtoTest extends TestCase
{
    #[Test]
    public function ToDtoShouldCastAmountFields(): void
    {
        $request = UpdateRecurringExpenseRequest::create('/api/recurring-expenses/1', 'PUT', [
            'amount_min' => '1000',
            'amount_max' => '2000',
            'is_active' => true,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertSame(1000.0, $dto->toArray()['amount_min']);
        $this->assertSame(2000.0, $dto->toArray()['amount_max']);
        $this->assertTrue($dto->toArray()['is_active']);
    }
}

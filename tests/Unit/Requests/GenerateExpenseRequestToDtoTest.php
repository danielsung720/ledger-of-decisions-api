<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GenerateExpenseRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GenerateExpenseRequestToDtoTest extends TestCase
{
    #[Test]
    public function to_dto_should_map_optional_fields(): void
    {
        $request = GenerateExpenseRequest::create('/api/recurring-expenses/1/generate', 'POST', [
            'date' => '2026-02-08',
            'amount' => '1500.75',
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toDto();

        $this->assertNotNull($dto->date);
        $this->assertSame('2026-02-08', $dto->date->toDateString());
        $this->assertSame('1500.75', $dto->amount);
    }
}

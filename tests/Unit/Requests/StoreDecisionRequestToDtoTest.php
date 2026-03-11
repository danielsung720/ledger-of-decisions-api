<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreDecisionRequest;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreDecisionRequestToDtoTest extends TestCase
{
    #[Test]
    public function to_create_dto_should_map_validated_payload(): void
    {
        $request = StoreDecisionRequest::create('/api/expenses/1/decision', 'POST', [
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => 'note',
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toCreateDto();

        $this->assertSame('necessity', $dto->intent);
        $this->assertSame('high', $dto->confidenceLevel);
        $this->assertSame('note', $dto->decisionNote);
    }

    #[Test]
    public function to_update_dto_should_map_validated_payload(): void
    {
        $request = StoreDecisionRequest::create('/api/expenses/1/decision', 'PUT', [
            'intent' => 'impulse',
            'decision_note' => 'updated',
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toUpdateDto();

        $this->assertSame('impulse', $dto->toArray()['intent']);
        $this->assertSame('updated', $dto->toArray()['decision_note']);
    }

    #[Test]
    public function to_update_dto_should_keep_null_confidence_level(): void
    {
        $request = StoreDecisionRequest::create('/api/expenses/1/decision', 'PUT', [
            'intent' => 'necessity',
            'confidence_level' => null,
        ]);

        $request->setContainer(app());
        $request->validateResolved();

        $dto = $request->toUpdateDto();

        $this->assertArrayHasKey('confidence_level', $dto->toArray());
        $this->assertNull($dto->toArray()['confidence_level']);
    }
}

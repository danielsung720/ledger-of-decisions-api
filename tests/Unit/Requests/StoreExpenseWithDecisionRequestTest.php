<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreExpenseWithDecisionRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreExpenseWithDecisionRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreExpenseWithDecisionRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    private function validData(array $overrides = []): array
    {
        return array_merge([
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-13',
            'intent' => 'necessity',
        ], $overrides);
    }

    #[Test]
    public function ItPassesWithValidMinimalData(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItPassesWithAllFields(): void
    {
        $validator = $this->validate([
            'amount' => 100,
            'currency' => 'TWD',
            'category' => 'food',
            'occurred_at' => '2026-02-13 12:00:00',
            'note' => '午餐',
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => '因為需要吃飯',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function AmountIsRequired(): void
    {
        $validator = $this->validate($this->validData(['amount' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountMustBeNumeric(): void
    {
        $validator = $this->validate($this->validData(['amount' => 'not-a-number']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function AmountCannotBeNegative(): void
    {
        $validator = $this->validate($this->validData(['amount' => -100]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('amount', $validator->errors()->toArray());
    }

    #[Test]
    public function CurrencyIsOptional(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function CurrencyMustBe3Characters(): void
    {
        $validator = $this->validate($this->validData(['currency' => 'TWDD']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('currency', $validator->errors()->toArray());
    }

    #[Test]
    public function CategoryIsRequired(): void
    {
        $validator = $this->validate($this->validData(['category' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validCategoryProvider')]
    public function CategoryAcceptsValidValues(string $category): void
    {
        $validator = $this->validate($this->validData(['category' => $category]));

        $this->assertTrue($validator->passes());
    }

    public static function validCategoryProvider(): array
    {
        return [
            'food' => ['food'],
            'transport' => ['transport'],
            'training' => ['training'],
            'living' => ['living'],
            'other' => ['other'],
        ];
    }

    #[Test]
    public function CategoryRejectsInvalidValues(): void
    {
        $validator = $this->validate($this->validData(['category' => 'invalid']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('category', $validator->errors()->toArray());
    }

    #[Test]
    public function OccurredAtIsRequired(): void
    {
        $validator = $this->validate($this->validData(['occurred_at' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('occurred_at', $validator->errors()->toArray());
    }

    #[Test]
    public function OccurredAtMustBeValidDate(): void
    {
        $validator = $this->validate($this->validData(['occurred_at' => 'not-a-date']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('occurred_at', $validator->errors()->toArray());
    }

    #[Test]
    public function NoteIsOptional(): void
    {
        $validator = $this->validate($this->validData(['note' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function NoteCannotExceed500Characters(): void
    {
        $validator = $this->validate($this->validData(['note' => str_repeat('a', 501)]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('note', $validator->errors()->toArray());
    }

    #[Test]
    public function IntentIsRequired(): void
    {
        $validator = $this->validate($this->validData(['intent' => null]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validIntentProvider')]
    public function IntentAcceptsValidValues(string $intent): void
    {
        $validator = $this->validate($this->validData(['intent' => $intent]));

        $this->assertTrue($validator->passes());
    }

    public static function validIntentProvider(): array
    {
        return [
            'necessity' => ['necessity'],
            'efficiency' => ['efficiency'],
            'enjoyment' => ['enjoyment'],
            'recovery' => ['recovery'],
            'impulse' => ['impulse'],
        ];
    }

    #[Test]
    public function IntentRejectsInvalidValues(): void
    {
        $validator = $this->validate($this->validData(['intent' => 'invalid']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }

    #[Test]
    public function ConfidenceLevelIsOptional(): void
    {
        $validator = $this->validate($this->validData());

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('validConfidenceLevelProvider')]
    public function ConfidenceLevelAcceptsValidValues(string $level): void
    {
        $validator = $this->validate($this->validData(['confidence_level' => $level]));

        $this->assertTrue($validator->passes());
    }

    public static function validConfidenceLevelProvider(): array
    {
        return [
            'high' => ['high'],
            'medium' => ['medium'],
            'low' => ['low'],
        ];
    }

    #[Test]
    public function ConfidenceLevelRejectsInvalidValues(): void
    {
        $validator = $this->validate($this->validData(['confidence_level' => 'invalid']));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('confidence_level', $validator->errors()->toArray());
    }

    #[Test]
    public function DecisionNoteIsOptional(): void
    {
        $validator = $this->validate($this->validData(['decision_note' => null]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function DecisionNoteCannotExceed1000Characters(): void
    {
        $validator = $this->validate($this->validData(['decision_note' => str_repeat('a', 1001)]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('decision_note', $validator->errors()->toArray());
    }

    #[Test]
    public function DecisionNoteCanBe1000Characters(): void
    {
        $validator = $this->validate($this->validData(['decision_note' => str_repeat('a', 1000)]));

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItReturnsChineseErrorMessages(): void
    {
        $validator = $this->validate([]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('請輸入消費金額', $errors['amount'][0]);
        $this->assertStringContainsString('請選擇消費類別', $errors['category'][0]);
        $this->assertStringContainsString('請輸入消費時間', $errors['occurred_at'][0]);
        $this->assertStringContainsString('請選擇決策意圖', $errors['intent'][0]);
    }

    #[Test]
    public function ToDtoShouldMapExpensePayload(): void
    {
        $payload = [
            'amount' => 100,
            'currency' => 'TWD',
            'category' => 'food',
            'occurred_at' => '2026-02-13',
            'note' => '午餐',
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => '測試',
        ];
        $request = StoreExpenseWithDecisionRequest::create('/api/entries', 'POST', $payload);
        $request->setValidator(Validator::make($payload, $request->rules(), $request->messages()));

        $dto = $request->toDto();

        $this->assertSame(100.0, $dto->expense->amount);
        $this->assertSame('TWD', $dto->expense->currency);
        $this->assertSame('food', $dto->expense->category);
        $this->assertSame('2026-02-13', $dto->expense->occurredAt);
        $this->assertSame('午餐', $dto->expense->note);
    }

    #[Test]
    public function ToDtoShouldMapDecisionPayload(): void
    {
        $payload = [
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-13',
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => '測試',
        ];
        $request = StoreExpenseWithDecisionRequest::create('/api/entries', 'POST', $payload);
        $request->setValidator(Validator::make($payload, $request->rules(), $request->messages()));

        $dto = $request->toDto();

        $this->assertSame('necessity', $dto->decision->intent);
        $this->assertSame('high', $dto->decision->confidenceLevel);
        $this->assertSame('測試', $dto->decision->decisionNote);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new StoreExpenseWithDecisionRequest();

        $this->assertTrue($request->authorize());
    }
}

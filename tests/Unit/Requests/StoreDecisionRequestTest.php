<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreDecisionRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StoreDecisionRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreDecisionRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function it_passes_with_valid_data(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_passes_with_all_fields(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => 'This is a test note',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function intent_is_required(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }

    #[Test]
    #[DataProvider('validIntentProvider')]
    public function intent_accepts_valid_values(string $intent): void
    {
        $validator = $this->validate([
            'intent' => $intent,
        ]);

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
    public function intent_rejects_invalid_values(): void
    {
        $validator = $this->validate([
            'intent' => 'invalid-intent',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('intent', $validator->errors()->toArray());
    }

    #[Test]
    public function confidence_level_is_optional(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    #[DataProvider('validConfidenceLevelProvider')]
    public function confidence_level_accepts_valid_values(string $level): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'confidence_level' => $level,
        ]);

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
    public function confidence_level_rejects_invalid_values(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'confidence_level' => 'invalid-level',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('confidence_level', $validator->errors()->toArray());
    }

    #[Test]
    public function confidence_level_accepts_null_value(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'confidence_level' => null,
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function decision_note_is_optional(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'decision_note' => null,
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function decision_note_cannot_exceed1000_characters(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'decision_note' => str_repeat('a', 1001),
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('decision_note', $validator->errors()->toArray());
    }

    #[Test]
    public function decision_note_can_be1000_characters(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'decision_note' => str_repeat('a', 1000),
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_returns_chinese_error_messages(): void
    {
        $validator = $this->validate([]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('請選擇決策意圖', $errors['intent'][0]);
    }

    #[Test]
    public function it_returns_chinese_error_for_invalid_intent(): void
    {
        $validator = $this->validate([
            'intent' => 'invalid',
        ]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('無效的決策意圖', $errors['intent'][0]);
    }

    #[Test]
    public function it_returns_chinese_error_for_invalid_confidence_level(): void
    {
        $validator = $this->validate([
            'intent' => 'necessity',
            'confidence_level' => 'invalid',
        ]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('無效的信心程度', $errors['confidence_level'][0]);
    }

    #[Test]
    public function request_is_authorized(): void
    {
        $request = new StoreDecisionRequest;

        $this->assertTrue($request->authorize());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\GetIntentsRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GetIntentsRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new GetIntentsRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function it_passes_with_empty_data(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function it_accepts_valid_date_range(): void
    {
        $validator = $this->validate([
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-28',
        ]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function start_date_must_be_before_or_equal_end_date(): void
    {
        $validator = $this->validate([
            'start_date' => '2026-03-01',
            'end_date' => '2026-02-01',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('start_date', $validator->errors()->toArray());
    }

    #[Test]
    public function end_date_must_be_after_or_equal_start_date(): void
    {
        $validator = $this->validate([
            'start_date' => '2026-02-10',
            'end_date' => '2026-02-01',
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('end_date', $validator->errors()->toArray());
    }

    #[Test]
    public function preset_accepts_supported_values_only(): void
    {
        $validator = $this->validate(['preset' => 'today']);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['preset' => 'this_week']);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['preset' => 'this_month']);
        $this->assertTrue($validator->passes());

        $validator = $this->validate(['preset' => 'invalid']);
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('preset', $validator->errors()->toArray());
    }

    #[Test]
    public function it_returns_chinese_error_messages(): void
    {
        $validator = $this->validate([
            'start_date' => 'invalid-date',
            'preset' => 'invalid',
        ]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('起始日期格式不正確', $errors['start_date'][0]);
        $this->assertStringContainsString('預設範圍僅支援 today、this_week、this_month', $errors['preset'][0]);
    }

    #[Test]
    public function request_is_authorized(): void
    {
        $request = new GetIntentsRequest();

        $this->assertTrue($request->authorize());
    }
}

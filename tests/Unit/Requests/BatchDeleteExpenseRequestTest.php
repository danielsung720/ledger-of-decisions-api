<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\BatchDeleteExpenseRequest;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BatchDeleteExpenseRequestTest extends TestCase
{
    private function validate(array $data): \Illuminate\Validation\Validator
    {
        $request = new BatchDeleteExpenseRequest();

        return Validator::make($data, $request->rules(), $request->messages());
    }

    #[Test]
    public function ItPassesWithValidIds(): void
    {
        $validator = $this->validate(['ids' => [1, 2, 3]]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function ItPassesWithSingleId(): void
    {
        $validator = $this->validate(['ids' => [1]]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function IdsIsRequired(): void
    {
        $validator = $this->validate([]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids', $validator->errors()->toArray());
    }

    #[Test]
    public function IdsMustBeArray(): void
    {
        $validator = $this->validate(['ids' => 'not-an-array']);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids', $validator->errors()->toArray());
    }

    #[Test]
    public function IdsCannotBeEmpty(): void
    {
        $validator = $this->validate(['ids' => []]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids', $validator->errors()->toArray());
    }

    #[Test]
    public function IdsCannotExceed100(): void
    {
        $ids = range(1, 101);
        $validator = $this->validate(['ids' => $ids]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids', $validator->errors()->toArray());
    }

    #[Test]
    public function IdsCanBe100(): void
    {
        $ids = range(1, 100);
        $validator = $this->validate(['ids' => $ids]);

        $this->assertTrue($validator->passes());
    }

    #[Test]
    public function EachIdMustBeInteger(): void
    {
        $validator = $this->validate(['ids' => [1, 'not-an-integer', 3]]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids.1', $validator->errors()->toArray());
    }

    #[Test]
    public function EachIdMustBePositive(): void
    {
        $validator = $this->validate(['ids' => [1, 0, 3]]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids.1', $validator->errors()->toArray());
    }

    #[Test]
    public function EachIdCannotBeNegative(): void
    {
        $validator = $this->validate(['ids' => [1, -1, 3]]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('ids.1', $validator->errors()->toArray());
    }

    #[Test]
    public function ItReturnsChineseErrorMessages(): void
    {
        $validator = $this->validate([]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('ids 欄位為必填', $errors['ids'][0]);
    }

    #[Test]
    public function ItReturnsChineseErrorForEmptyArray(): void
    {
        $validator = $this->validate(['ids' => []]);

        $errors = $validator->errors()->toArray();

        $this->assertTrue(
            str_contains($errors['ids'][0], '請至少選擇一筆') ||
            str_contains($errors['ids'][0], 'ids 欄位為必填')
        );
    }

    #[Test]
    public function ItReturnsChineseErrorForMaxExceeded(): void
    {
        $ids = range(1, 101);
        $validator = $this->validate(['ids' => $ids]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('最多只能刪除 100 筆', $errors['ids'][0]);
    }

    #[Test]
    public function ItReturnsChineseErrorForInvalidId(): void
    {
        $validator = $this->validate(['ids' => ['invalid']]);

        $errors = $validator->errors()->toArray();

        $this->assertStringContainsString('ID 必須是整數', $errors['ids.0'][0]);
    }

    #[Test]
    public function RequestIsAuthorized(): void
    {
        $request = new BatchDeleteExpenseRequest();

        $this->assertTrue($request->authorize());
    }
}

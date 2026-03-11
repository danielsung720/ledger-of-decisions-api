<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\ExpenseResource;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ToArrayShouldMapExpenseFields(): void
    {
        $expense = Expense::factory()->create([
            'amount' => 1234.56,
            'note' => 'Lunch',
        ]);

        $data = (new ExpenseResource($expense))->toArray(new Request());

        $this->assertSame($expense->id, $data['id']);
        $this->assertSame('1234.56', $data['amount']);
        $this->assertSame('TWD', $data['currency']);
        $this->assertSame($expense->category->value, $data['category']);
        $this->assertSame($expense->category->label(), $data['category_label']);
        $this->assertSame('Lunch', $data['note']);
        $this->assertSame($expense->occurred_at->toIso8601String(), $data['occurred_at']);
    }
}

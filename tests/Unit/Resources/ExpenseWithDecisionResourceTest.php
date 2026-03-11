<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\ExpenseWithDecisionResource;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\RecurringExpense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExpenseWithDecisionResourceTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ToArrayShouldIncludeLoadedDecisionAndRecurringExpense(): void
    {
        $recurringExpense = RecurringExpense::factory()->create(['name' => 'Monthly Rent']);
        $expense = Expense::factory()->create([
            'recurring_expense_id' => $recurringExpense->id,
        ]);
        Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => 'necessity',
            'confidence_level' => 'high',
        ]);

        $expense->load(['decision', 'recurringExpense']);
        $data = (new ExpenseWithDecisionResource($expense))->toArray(new Request());

        $this->assertSame($expense->id, $data['id']);
        $this->assertSame('necessity', $data['decision']['intent']);
        $this->assertSame('high', $data['decision']['confidence_level']);
        $this->assertSame($recurringExpense->id, $data['recurring_expense']['id']);
        $this->assertTrue($data['is_from_recurring']);
    }
}

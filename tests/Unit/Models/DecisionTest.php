<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ConfidenceLevel;
use App\Enums\Intent;
use App\Models\Decision;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DecisionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ItHasCorrectFillableAttributes(): void
    {
        $decision = new Decision();
        $fillable = $decision->getFillable();

        $this->assertContains('expense_id', $fillable);
        $this->assertContains('intent', $fillable);
        $this->assertContains('confidence_level', $fillable);
        $this->assertContains('decision_note', $fillable);
    }

    #[Test]
    public function ItCastsIntentToEnum(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
        ]);

        $this->assertInstanceOf(Intent::class, $decision->intent);
        $this->assertSame(Intent::Necessity, $decision->intent);
    }

    #[Test]
    public function ItCastsConfidenceLevelToEnum(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'confidence_level' => ConfidenceLevel::High,
        ]);

        $this->assertInstanceOf(ConfidenceLevel::class, $decision->confidence_level);
        $this->assertSame(ConfidenceLevel::High, $decision->confidence_level);
    }

    #[Test]
    public function ItCanOmitConfidenceLevel(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::create([
            'expense_id' => $expense->id,
            'intent' => Intent::Necessity,
            // confidence_level is not set
        ]);

        $this->assertNull($decision->confidence_level);
    }

    #[Test]
    public function ItBelongsToExpense(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create(['expense_id' => $expense->id]);

        $this->assertInstanceOf(Expense::class, $decision->expense);
        $this->assertTrue($decision->expense->is($expense));
    }

    #[Test]
    public function ItCanBeCreatedWithFactory(): void
    {
        $decision = Decision::factory()->create();

        $this->assertDatabaseHas('decisions', ['id' => $decision->id]);
    }

    #[Test]
    public function NecessityFactoryStateCreatesNecessityIntent(): void
    {
        $decision = Decision::factory()->necessity()->create();

        $this->assertSame(Intent::Necessity, $decision->intent);
    }

    #[Test]
    public function ImpulseFactoryStateCreatesImpulseIntent(): void
    {
        $decision = Decision::factory()->impulse()->create();

        $this->assertSame(Intent::Impulse, $decision->intent);
    }

    #[Test]
    public function HighConfidenceFactoryStateCreatesHighConfidence(): void
    {
        $decision = Decision::factory()->highConfidence()->create();

        $this->assertSame(ConfidenceLevel::High, $decision->confidence_level);
    }

    #[Test]
    public function ItCanHaveDecisionNote(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'decision_note' => 'This is a test note',
        ]);

        $this->assertSame('This is a test note', $decision->decision_note);
    }

    #[Test]
    public function ItCanHaveNullDecisionNote(): void
    {
        $expense = Expense::factory()->create();
        $decision = Decision::factory()->create([
            'expense_id' => $expense->id,
            'decision_note' => null,
        ]);

        $this->assertNull($decision->decision_note);
    }
}

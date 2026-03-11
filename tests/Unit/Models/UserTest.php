<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\CashFlowItem;
use App\Models\Expense;
use App\Models\Income;
use App\Models\RecurringExpense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function ItHasCorrectFillableAttributes(): void
    {
        $user = new User();

        $this->assertSame(['name', 'email', 'password'], $user->getFillable());
    }

    #[Test]
    public function ItHasCorrectHiddenAttributes(): void
    {
        $user = new User();

        $this->assertContains('password', $user->getHidden());
        $this->assertContains('remember_token', $user->getHidden());
    }

    #[Test]
    public function ItCastsEmailVerifiedAtToDatetime(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => '2026-02-14 10:00:00',
        ]);

        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('2026-02-14 10:00:00', $user->email_verified_at?->format('Y-m-d H:i:s'));
    }

    #[Test]
    public function ItHashesPasswordOnAssignment(): void
    {
        $user = User::factory()->create([
            'password' => 'plainpassword',
        ]);

        $this->assertNotSame('plainpassword', $user->password);
    }

    #[Test]
    public function ItHasManyExpenses(): void
    {
        $user = User::factory()->create();
        Expense::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->expenses);
    }

    #[Test]
    public function ItHasManyRecurringExpenses(): void
    {
        $user = User::factory()->create();
        RecurringExpense::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->recurringExpenses);
    }

    #[Test]
    public function ItHasManyIncomes(): void
    {
        $user = User::factory()->create();
        Income::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->incomes);
    }

    #[Test]
    public function ItHasManyCashFlowItems(): void
    {
        $user = User::factory()->create();
        CashFlowItem::factory()->count(2)->create(['user_id' => $user->id]);

        $this->assertCount(2, $user->cashFlowItems);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\Category;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BelongsToUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function AutomaticallyAssignsAuthenticatedUserIdOnCreate(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $expense = Expense::create([
            'amount' => 123.45,
            'currency' => 'TWD',
            'category' => Category::Food,
            'occurred_at' => now(),
            'user_id' => null,
        ]);

        $this->assertSame($user->id, $expense->user_id);
    }

    #[Test]
    public function KeepsProvidedUserIdOnCreate(): void
    {
        $authenticatedUser = User::factory()->create();
        $assignedUser = User::factory()->create();
        $this->actingAs($authenticatedUser);

        $expense = Expense::create([
            'amount' => 200,
            'currency' => 'TWD',
            'category' => Category::Living,
            'occurred_at' => now(),
            'user_id' => $assignedUser->id,
        ]);

        $this->assertSame($assignedUser->id, $expense->user_id);
    }

    #[Test]
    public function AppliesGlobalUserScopeOnQuery(): void
    {
        $authenticatedUser = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->actingAs($authenticatedUser);

        $ownExpense = Expense::factory()->create(['user_id' => $authenticatedUser->id]);
        Expense::factory()->create(['user_id' => $otherUser->id]);

        $visibleIds = Expense::query()->pluck('id')->all();

        $this->assertSame([$ownExpense->id], $visibleIds);
        $this->assertCount(2, Expense::withoutGlobalScopes()->get());
    }

    #[Test]
    public function UserMethodReturnsBelongsToRelation(): void
    {
        $user = User::factory()->create();
        $expense = Expense::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(BelongsTo::class, $expense->user());
        $this->assertTrue($expense->user->is($user));
    }
}

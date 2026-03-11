<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Entry\CreateEntryDto;
use App\Events\ExpenseWithDecisionCreated;
use App\Models\Expense;
use App\Repositories\EntryRepository;
use App\Services\EntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class EntryServiceTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private EntryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->service = new EntryService(new EntryRepository);
    }

    #[Test]
    public function create_should_persist_expense_and_decision_for_authenticated_user(): void
    {
        Event::fake([ExpenseWithDecisionCreated::class]);

        $expense = $this->service->create(
            CreateEntryDto::fromArray([
                'amount' => 150,
                'category' => 'food',
                'occurred_at' => '2026-02-14 12:00:00',
                'note' => 'Lunch',
                'intent' => 'necessity',
                'confidence_level' => 'high',
                'decision_note' => 'Need food',
            ])
        );

        $this->assertInstanceOf(Expense::class, $expense);
        $this->assertSame($this->user->id, $expense->user_id);
        $this->assertTrue($expense->relationLoaded('decision'));
        $this->assertNotNull($expense->decision);

        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'user_id' => $this->user->id,
            'amount' => 150,
            'category' => 'food',
        ]);

        $this->assertDatabaseHas('decisions', [
            'expense_id' => $expense->id,
            'intent' => 'necessity',
            'confidence_level' => 'high',
            'decision_note' => 'Need food',
        ]);
        Event::assertDispatched(ExpenseWithDecisionCreated::class, function (ExpenseWithDecisionCreated $event) use ($expense): bool {
            return $event->expense->id === $expense->id;
        });
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\DTO\Entry\CreateEntryDto;
use App\Models\Decision;
use App\Repositories\EntryRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class EntryRepositoryTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    private EntryRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
        $this->repository = new EntryRepository;
    }

    #[Test]
    public function create_with_decision_should_rollback_expense_when_decision_creation_fails(): void
    {
        Decision::creating(static function (): void {
            throw new RuntimeException('forced decision creation failure');
        });

        $thrown = false;

        try {
            $this->repository->createWithDecision(
                CreateEntryDto::fromArray([
                    'amount' => 100,
                    'category' => 'food',
                    'occurred_at' => '2026-02-16 12:00:00',
                    'intent' => 'necessity',
                ])
            );
        } catch (RuntimeException) {
            $thrown = true;
        } finally {
            Decision::flushEventListeners();
        }

        $this->assertTrue($thrown);
        $this->assertDatabaseCount('expenses', 0);
        $this->assertDatabaseCount('decisions', 0);
    }
}

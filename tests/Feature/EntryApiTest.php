<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tests\Traits\AuthenticatesUser;

class EntryApiTest extends TestCase
{
    use AuthenticatesUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuthenticatesUser();
    }

    #[Test]
    public function CanCreateExpenseWithDecision(): void
    {
        $response = $this->postJson('/api/entries', [
            'amount' => 250,
            'category' => 'transport',
            'occurred_at' => '2026-02-06 08:00:00',
            'note' => '計程車',
            'intent' => 'efficiency',
            'confidence_level' => 'high',
            'decision_note' => '趕時間必須搭計程車',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'amount' => '250.00',
                    'currency' => 'TWD',
                    'category' => 'transport',
                    'category_label' => '交通',
                    'note' => '計程車',
                    'decision' => [
                        'intent' => 'efficiency',
                        'intent_label' => '效率',
                        'confidence_level' => 'high',
                        'confidence_level_label' => '高',
                        'decision_note' => '趕時間必須搭計程車',
                    ],
                ],
            ]);

        $this->assertDatabaseHas('expenses', [
            'user_id' => $this->user->id,
            'amount' => 250,
            'category' => 'transport',
        ]);

        $this->assertDatabaseHas('decisions', [
            'intent' => 'efficiency',
            'confidence_level' => 'high',
        ]);
    }

    #[Test]
    public function ValidatesBothExpenseAndDecisionFields(): void
    {
        $response = $this->postJson('/api/entries', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount', 'category', 'occurred_at', 'intent']);
    }

    #[Test]
    public function TransactionRollsBackOnFailure(): void
    {
        $response = $this->postJson('/api/entries', [
            'amount' => 100,
            'category' => 'food',
            'occurred_at' => '2026-02-06 12:00:00',
            'intent' => 'invalid_intent',
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('expenses', 0);
    }

    #[Test]
    public function EntryEndpointRequiresAuthentication(): void
    {
        auth()->logout();

        $this->postJson('/api/entries', [])->assertStatus(401);
    }
}

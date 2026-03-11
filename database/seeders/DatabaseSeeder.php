<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'Expense CRUD E2E User',
            'email' => 'expense_crud@example.com',
        ]);

        User::factory()->create([
            'name' => 'Batch Delete E2E User',
            'email' => 'batch_delete@example.com',
        ]);

        User::factory()->create([
            'name' => 'E2E Core User',
            'email' => 'e2e_core@example.com',
        ]);

        User::factory()->create([
            'name' => 'Auth E2E User',
            'email' => 'auth_e2e@example.com',
        ]);

        User::factory()->create([
            'name' => 'Recurring E2E User',
            'email' => 'recurring_e2e@example.com',
        ]);

        User::factory()->create([
            'name' => 'Cashflow Income E2E User',
            'email' => 'cashflow_income@example.com',
        ]);

        User::factory()->create([
            'name' => 'Cashflow Item E2E User',
            'email' => 'cashflow_item@example.com',
        ]);
    }
}

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

        // Per-browser isolated users for parallel test runs
        // Each browser gets its own accounts to avoid data contention
        $browsers = ['chromium', 'firefox', 'webkit', 'mobile_chrome', 'mobile_safari'];

        $userGroups = [
            ['E2E Core User',          'e2e_core'],
            ['Expense CRUD E2E User',  'expense_crud'],
            ['Batch Delete E2E User',  'batch_delete'],
            ['Auth E2E User',          'auth_e2e'],
            ['Recurring E2E User',     'recurring_e2e'],
            ['Cashflow Income E2E User', 'cashflow_income'],
            ['Cashflow Item E2E User', 'cashflow_item'],
        ];

        foreach ($userGroups as [$name, $prefix]) {
            foreach ($browsers as $browser) {
                User::factory()->create([
                    'name'  => "{$name} ({$browser})",
                    'email' => "{$prefix}_{$browser}@example.com",
                ]);
            }
        }
    }
}

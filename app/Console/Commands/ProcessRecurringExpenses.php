<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\RecurringExpenseService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessRecurringExpenses extends Command
{
    protected $signature = 'recurring-expenses:process
                            {--date= : Process up to this date (default: today)}
                            {--dry-run : Show what would be processed without actually generating expenses}';

    protected $description = '處理到期的固定支出並自動產生消費記錄';

    public function __construct(
        private readonly RecurringExpenseService $service
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $isDryRun = $this->option('dry-run');

        $this->info("處理日期：{$date->toDateString()}");

        if ($isDryRun) {
            $this->warn('乾跑模式：不會實際產生消費記錄');
            return $this->dryRun($date);
        }

        $expenses = $this->service->processAllDue($date);

        if ($expenses->isEmpty()) {
            $this->info('沒有需要處理的固定支出');
            return Command::SUCCESS;
        }

        $this->info("已產生 {$expenses->count()} 筆消費記錄：");
        $this->newLine();

        $tableData = $expenses->map(fn (\App\Models\Expense $expense) => [
            $expense->id,
            $expense->recurringExpense->name ?? '-',
            "\${$expense->amount}",
            $expense->category->label(),
            $expense->occurred_at->toDateString(),
        ])->toArray();

        $this->table(
            ['ID', '固定支出', '金額', '類別', '發生日期'],
            $tableData
        );

        return Command::SUCCESS;
    }

    private function dryRun(Carbon $date): int
    {
        $dueExpenses = \App\Models\RecurringExpense::due($date)->get();

        if ($dueExpenses->isEmpty()) {
            $this->info('沒有需要處理的固定支出');
            return Command::SUCCESS;
        }

        $this->info("找到 {$dueExpenses->count()} 筆待處理的固定支出：");
        $this->newLine();

        $tableData = [];

        foreach ($dueExpenses as $recurring) {
            $occurrences = $recurring->getMissedOccurrences($date);

            foreach ($occurrences as $occurrenceDate) {
                $tableData[] = [
                    $recurring->id,
                    $recurring->name,
                    $recurring->hasAmountRange()
                        ? "\${$recurring->amount_min} ~ \${$recurring->amount_max}"
                        : "\${$recurring->amount_min}",
                    $recurring->category->label(),
                    $occurrenceDate->toDateString(),
                ];
            }
        }

        $this->table(
            ['固定支出 ID', '名稱', '金額', '類別', '將產生日期'],
            $tableData
        );

        $this->newLine();
        $this->info("將產生 " . count($tableData) . " 筆消費記錄");

        return Command::SUCCESS;
    }
}

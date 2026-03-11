<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\RecurringExpense\CreateRecurringExpenseDto;
use App\DTO\RecurringExpense\RecurringExpensePaginateQueryDto;
use App\DTO\RecurringExpense\RecurringExpenseUpcomingQueryDto;
use App\DTO\RecurringExpense\UpdateRecurringExpenseDto;
use App\Models\Decision;
use App\Models\Expense;
use App\Models\RecurringExpense;
use App\Repositories\RecurringExpenseRepository;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Application service for recurring expense lifecycle and generation workflows.
 */
class RecurringExpenseService
{
    public function __construct(
        private readonly RecurringExpenseRepository $recurringExpenseRepository
    ) {
    }

    public function paginate(RecurringExpensePaginateQueryDto $query): LengthAwarePaginator
    {
        return $this->recurringExpenseRepository->paginate($query);
    }

    /**
     * Create a new recurring expense.
     */
    public function create(CreateRecurringExpenseDto $payload): RecurringExpense
    {
        return $this->recurringExpenseRepository->create($payload);
    }

    /**
     * Retrieve one recurring expense.
     */
    public function show(RecurringExpense $recurringExpense): RecurringExpense
    {
        return $this->recurringExpenseRepository->show($recurringExpense);
    }

    /**
     * Update one recurring expense with reactivation handling.
     */
    public function update(RecurringExpense $recurringExpense, UpdateRecurringExpenseDto $payload): RecurringExpense
    {
        $attributes = $payload->toArray();

        if (
            array_key_exists('is_active', $attributes) &&
            $attributes['is_active'] === true &&
            ! $recurringExpense->is_active
        ) {
            $this->reactivate($recurringExpense);
            unset($attributes['is_active']);
        }

        return $this->recurringExpenseRepository->update(
            $recurringExpense,
            new UpdateRecurringExpenseDto($attributes)
        );
    }

    /**
     * Delete one recurring expense.
     */
    public function delete(RecurringExpense $recurringExpense): void
    {
        $this->recurringExpenseRepository->delete($recurringExpense);
    }

    /**
     * Process all due recurring expenses and generate expense records.
     *
     * @return Collection<int, Expense> Generated expenses
     */
    public function processAllDue(?Carbon $upToDate = null): Collection
    {
        $upToDate = $upToDate ?? Carbon::today();
        $generatedExpenses = collect();

        $dueRecurringExpenses = RecurringExpense::due($upToDate)->get();

        foreach ($dueRecurringExpenses as $recurringExpense) {
            $expenses = $this->processRecurringExpense($recurringExpense, $upToDate);
            $generatedExpenses = $generatedExpenses->merge($expenses);
        }

        return $generatedExpenses;
    }

    /**
     * Process a single recurring expense and generate all missed expense records.
     *
     * @return Collection<int, Expense> Generated expenses
     */
    public function processRecurringExpense(
        RecurringExpense $recurringExpense,
        ?Carbon $upToDate = null
    ): Collection {
        $upToDate = $upToDate ?? Carbon::today();
        $generatedExpenses = collect();

        if (! $recurringExpense->is_active) {
            return $generatedExpenses;
        }

        $missedOccurrences = $recurringExpense->getMissedOccurrences($upToDate);

        foreach ($missedOccurrences as $occurrenceDate) {
            try {
                $expense = $this->generateExpenseForDate($recurringExpense, $occurrenceDate);
                $generatedExpenses->push($expense);

                Log::info('Generated expense from recurring expense', [
                    'recurring_expense_id' => $recurringExpense->id,
                    'recurring_expense_name' => $recurringExpense->name,
                    'expense_id' => $expense->id,
                    'occurred_at' => $occurrenceDate->toDateString(),
                    'amount' => $expense->amount,
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to generate expense from recurring expense', [
                    'recurring_expense_id' => $recurringExpense->id,
                    'occurrence_date' => $occurrenceDate->toDateString(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        if ($generatedExpenses->isNotEmpty()) {
            $this->updateNextOccurrence($recurringExpense, $upToDate);
        }

        return $generatedExpenses;
    }

    /**
     * Generate an expense record for a specific occurrence date.
     */
    public function generateExpenseForDate(
        RecurringExpense $recurringExpense,
        Carbon $occurrenceDate
    ): Expense {
        return DB::transaction(function () use ($recurringExpense, $occurrenceDate) {
            $expense = Expense::create([
                'user_id' => $recurringExpense->user_id,
                'amount' => $recurringExpense->generateAmount(),
                'currency' => $recurringExpense->currency,
                'category' => $recurringExpense->category,
                'occurred_at' => $occurrenceDate->startOfDay(),
                'note' => $this->buildExpenseNote($recurringExpense),
                'recurring_expense_id' => $recurringExpense->id,
            ]);

            if ($recurringExpense->default_intent) {
                Decision::create([
                    'expense_id' => $expense->id,
                    'intent' => $recurringExpense->default_intent,
                    'confidence_level' => 'high',
                    'decision_note' => "自動從固定支出「{$recurringExpense->name}」產生",
                ]);
            }

            return $expense;
        });
    }

    /**
     * Manually generate an expense for today (or specified date).
     */
    public function generateManually(
        RecurringExpense $recurringExpense,
        ?Carbon $date = null,
        ?string $amount = null
    ): Expense {
        $date = $date ?? Carbon::today();

        return DB::transaction(function () use ($recurringExpense, $date, $amount) {
            $expense = Expense::create([
                'user_id' => $recurringExpense->user_id,
                'amount' => $amount ?? $recurringExpense->generateAmount(),
                'currency' => $recurringExpense->currency,
                'category' => $recurringExpense->category,
                'occurred_at' => $date->startOfDay(),
                'note' => $this->buildExpenseNote($recurringExpense, true),
                'recurring_expense_id' => $recurringExpense->id,
            ]);

            if ($recurringExpense->default_intent) {
                Decision::create([
                    'expense_id' => $expense->id,
                    'intent' => $recurringExpense->default_intent,
                    'confidence_level' => 'high',
                    'decision_note' => "手動從固定支出「{$recurringExpense->name}」產生",
                ]);
            }

            return $expense;
        });
    }

    /**
     * Update next occurrence after processing.
     */
    private function updateNextOccurrence(RecurringExpense $recurringExpense, Carbon $upToDate): void
    {
        $next = $recurringExpense->calculateNextOccurrence($upToDate);

        if ($next === null || ($recurringExpense->end_date && $next->gt($recurringExpense->end_date))) {
            // Deactivate but keep the last next_occurrence value (DB doesn't allow null)
            $recurringExpense->update([
                'is_active' => false,
            ]);
        } else {
            $recurringExpense->update([
                'next_occurrence' => $next,
            ]);
        }
    }

    /**
     * Build note for generated expense.
     */
    private function buildExpenseNote(RecurringExpense $recurringExpense, bool $isManual = false): string
    {
        $prefix = $isManual ? '[手動] ' : '';
        $note = "{$prefix}固定支出：{$recurringExpense->name}";

        if ($recurringExpense->note) {
            $note .= " - {$recurringExpense->note}";
        }

        return $note;
    }

    /**
     * Get upcoming recurring expenses within specified days.
     *
     * @return Collection<int, RecurringExpense>
     */
    public function getUpcoming(RecurringExpenseUpcomingQueryDto $query): Collection
    {
        return $this->recurringExpenseRepository->getUpcoming($query);
    }

    /**
     * Get expense history for a recurring expense.
     *
     * @return Collection<int, Expense>
     */
    public function getHistory(RecurringExpense $recurringExpense, int $limit = 10): Collection
    {
        return $this->recurringExpenseRepository->getHistory($recurringExpense, $limit);
    }

    /**
     * Deactivate a recurring expense.
     */
    public function deactivate(RecurringExpense $recurringExpense): void
    {
        $recurringExpense->update(['is_active' => false]);
    }

    /**
     * Reactivate a recurring expense and recalculate next occurrence.
     */
    public function reactivate(RecurringExpense $recurringExpense): void
    {
        $today = Carbon::today();
        $next = $recurringExpense->calculateNextOccurrence($today);

        if ($next === null || ($recurringExpense->end_date && $next->gt($recurringExpense->end_date))) {
            return;
        }

        $recurringExpense->update([
            'is_active' => true,
            'next_occurrence' => $next,
        ]);
    }
}

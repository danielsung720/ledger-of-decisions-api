<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\CashFlowItemController;
use App\Http\Controllers\DecisionController;
use App\Http\Controllers\EntryController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\RecurringExpenseController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\UserPreferencesController;
use Illuminate\Support\Facades\Route;

// Health check for Fly.io
Route::get('health', fn () => response()->json(['status' => 'ok']));

// Auth routes with rate limiting (relaxed in testing environment)
$authThrottle = app()->environment('testing') ? 'throttle:1000,1' : 'throttle:5,1';
$verifyThrottle = app()->environment('testing') ? 'throttle:1000,1' : 'throttle:10,1';

Route::middleware($authThrottle)->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
});

Route::middleware($verifyThrottle)->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-email', [AuthController::class, 'verifyEmail']);
    Route::post('resend-verification', [AuthController::class, 'resendVerification']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
});

// Protected routes (authentication required)
Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    Route::put('user/password', [AuthController::class, 'updatePassword']);

    // User Preferences
    Route::get('user/preferences', [UserPreferencesController::class, 'show']);
    Route::put('user/preferences', [UserPreferencesController::class, 'update']);

    // Expense CRUD
    Route::delete('expenses/batch', [ExpenseController::class, 'batchDestroy']);
    Route::apiResource('expenses', ExpenseController::class);

    // Decision tagging (nested under expense)
    Route::prefix('expenses/{expense}')->group(function () {
        Route::post('decision', [DecisionController::class, 'store']);
        Route::get('decision', [DecisionController::class, 'show']);
        Route::put('decision', [DecisionController::class, 'update']);
        Route::delete('decision', [DecisionController::class, 'destroy']);
    });

    // Combined entry (expense + decision in one request)
    Route::post('entries', [EntryController::class, 'store']);

    // Statistics
    Route::prefix('statistics')->group(function () {
        Route::get('intents', [StatisticsController::class, 'intents']);
        Route::get('summary', [StatisticsController::class, 'summary']);
        Route::get('trends', [StatisticsController::class, 'trends']);
    });

    // Recurring Expenses
    Route::get('recurring-expenses/upcoming', [RecurringExpenseController::class, 'upcoming']);
    Route::apiResource('recurring-expenses', RecurringExpenseController::class);
    Route::prefix('recurring-expenses/{recurring_expense}')->group(function () {
        Route::post('generate', [RecurringExpenseController::class, 'generate']);
        Route::get('history', [RecurringExpenseController::class, 'history']);
    });

    // Cash Flow
    Route::apiResource('incomes', IncomeController::class);
    Route::apiResource('cash-flow-items', CashFlowItemController::class);
    Route::prefix('cash-flow')->group(function () {
        Route::get('summary', [CashFlowController::class, 'summary']);
        Route::get('projection', [CashFlowController::class, 'projection']);
    });
});

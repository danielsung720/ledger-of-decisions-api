<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table): void {
            $table->index(['user_id', 'occurred_at'], 'expenses_user_occurred_at_index');
            $table->index(['user_id', 'category', 'occurred_at'], 'expenses_user_category_occurred_at_index');
        });

        Schema::table('decisions', function (Blueprint $table): void {
            $table->index(['intent', 'expense_id'], 'decisions_intent_expense_id_index');
            $table->index(['confidence_level', 'expense_id'], 'decisions_confidence_expense_id_index');
        });

        Schema::table('incomes', function (Blueprint $table): void {
            $table->index(['user_id', 'is_active', 'created_at'], 'incomes_user_active_created_at_index');
            $table->index(['user_id', 'frequency_type', 'created_at'], 'incomes_user_frequency_created_at_index');
        });

        Schema::table('cash_flow_items', function (Blueprint $table): void {
            $table->index(['user_id', 'is_active', 'created_at'], 'cash_flow_items_user_active_created_at_index');
            $table->index(['user_id', 'category', 'created_at'], 'cash_flow_items_user_category_created_at_index');
            $table->index(['user_id', 'frequency_type', 'created_at'], 'cash_flow_items_user_frequency_created_at_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_flow_items', function (Blueprint $table): void {
            $table->dropIndex('cash_flow_items_user_active_created_at_index');
            $table->dropIndex('cash_flow_items_user_category_created_at_index');
            $table->dropIndex('cash_flow_items_user_frequency_created_at_index');
        });

        Schema::table('incomes', function (Blueprint $table): void {
            $table->dropIndex('incomes_user_active_created_at_index');
            $table->dropIndex('incomes_user_frequency_created_at_index');
        });

        Schema::table('decisions', function (Blueprint $table): void {
            $table->dropIndex('decisions_intent_expense_id_index');
            $table->dropIndex('decisions_confidence_expense_id_index');
        });

        Schema::table('expenses', function (Blueprint $table): void {
            $table->dropIndex('expenses_user_occurred_at_index');
            $table->dropIndex('expenses_user_category_occurred_at_index');
        });
    }
};

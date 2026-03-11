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
        Schema::create('recurring_expenses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('固定支出名稱');
            $table->decimal('amount_min', 12, 2)->comment('最小金額');
            $table->decimal('amount_max', 12, 2)->nullable()->comment('最大金額，空值表示固定金額');
            $table->string('currency', 3)->default('TWD')->comment('幣別');
            $table->string('category')->comment('消費類別');
            $table->string('frequency_type')->comment('週期類型：daily/weekly/monthly/yearly');
            $table->unsignedTinyInteger('frequency_interval')->default(1)->comment('間隔數（每N天/週/月/年）');
            $table->unsignedTinyInteger('day_of_month')->nullable()->comment('每月第N天（1-31）');
            $table->unsignedTinyInteger('month_of_year')->nullable()->comment('每年M月（1-12）');
            $table->unsignedTinyInteger('day_of_week')->nullable()->comment('每週幾（0=週日, 1-6=週一至週六）');
            $table->date('start_date')->comment('開始日期');
            $table->date('end_date')->nullable()->comment('結束日期');
            $table->date('next_occurrence')->comment('下次執行日期');
            $table->string('default_intent')->nullable()->comment('預設決策意圖');
            $table->string('note', 500)->nullable()->comment('備註');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();

            $table->index('next_occurrence');
            $table->index('is_active');
            $table->index('category');
            $table->index(['is_active', 'next_occurrence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recurring_expenses');
    }
};

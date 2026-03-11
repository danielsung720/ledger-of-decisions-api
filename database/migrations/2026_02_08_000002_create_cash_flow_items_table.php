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
        Schema::create('cash_flow_items', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('項目名稱');
            $table->decimal('amount', 12, 2)->comment('金額');
            $table->string('currency', 3)->default('TWD')->comment('幣別');
            $table->string('category')->comment('消費類別');
            $table->string('frequency_type')->comment('週期類型：monthly/yearly/one_time');
            $table->unsignedTinyInteger('frequency_interval')->default(1)->comment('間隔數（每N月/年）');
            $table->date('start_date')->comment('開始日期');
            $table->date('end_date')->nullable()->comment('結束日期');
            $table->string('note', 500)->nullable()->comment('備註');
            $table->boolean('is_active')->default(true)->comment('是否啟用');
            $table->timestamps();

            $table->index('is_active');
            $table->index('category');
            $table->index('frequency_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_flow_items');
    }
};

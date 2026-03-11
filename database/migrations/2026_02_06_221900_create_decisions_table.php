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
        Schema::create('decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_id')->constrained()->onDelete('cascade')->comment('關聯的消費記錄 ID');
            $table->string('intent')->comment('決策意圖：necessity/efficiency/enjoyment/recovery/impulse');
            $table->string('confidence_level')->default('medium')->comment('信心程度：high/medium/low');
            $table->string('decision_note', 1000)->nullable()->comment('決策備註');
            $table->timestamps();

            $table->unique('expense_id');
            $table->index('intent');
            $table->index('confidence_level');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('decisions');
    }
};

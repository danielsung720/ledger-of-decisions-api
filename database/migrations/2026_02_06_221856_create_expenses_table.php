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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->decimal('amount', 12, 2)->comment('消費金額');
            $table->string('currency', 3)->default('TWD')->comment('幣別，預設 TWD');
            $table->string('category')->comment('消費類別：food/transport/training/living/other');
            $table->dateTime('occurred_at')->comment('消費發生時間');
            $table->string('note', 500)->nullable()->comment('消費備註');
            $table->timestamps();

            $table->index('occurred_at');
            $table->index('category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

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
        Schema::create('branch_week_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_time_history_id')->constrained('branch_time_histories')->onDelete('cascade');
            $table->foreignId('week_day_id')->constrained('week_days')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_week_days');
    }
};

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
        Schema::table('meal_assignments', function (Blueprint $table) {
            // Remove unique constraint that prevented multiple recipes per slot
            $table->dropUnique(['meal_plan_id', 'date', 'meal_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('meal_assignments', function (Blueprint $table) {
            // Restore unique constraint for rollback
            $table->unique(['meal_plan_id', 'date', 'meal_type']);
        });
    }
};

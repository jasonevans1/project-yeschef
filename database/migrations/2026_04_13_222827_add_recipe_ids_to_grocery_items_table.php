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
        Schema::table('grocery_items', function (Blueprint $table) {
            $table->json('recipe_ids')->nullable()->after('original_values');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('grocery_items', function (Blueprint $table) {
            $table->dropColumn('recipe_ids');
        });
    }
};

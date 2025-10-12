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
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recipe_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ingredient_id')->constrained()->restrictOnDelete();
            $table->decimal('quantity', 10, 3);
            $table->enum('unit', [
                'tsp',
                'tbsp',
                'fl_oz',
                'cup',
                'pint',
                'quart',
                'gallon',
                'ml',
                'liter',
                'oz',
                'lb',
                'gram',
                'kg',
                'whole',
                'clove',
                'slice',
                'piece',
                'pinch',
                'dash',
                'to_taste',
            ]);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('notes')->nullable();
            $table->timestamps();

            // Indexes
            $table->unique(['recipe_id', 'ingredient_id']);
            $table->index(['recipe_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};

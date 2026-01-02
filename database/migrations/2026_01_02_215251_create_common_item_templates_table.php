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
        Schema::create('common_item_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->enum('category', [
                'produce',
                'dairy',
                'meat',
                'seafood',
                'pantry',
                'frozen',
                'bakery',
                'deli',
                'beverages',
                'other',
            ]);
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
            ])->nullable();
            $table->decimal('default_quantity', 8, 3)->nullable();
            $table->text('search_keywords')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            // Indexes for autocomplete performance
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('common_item_templates');
    }
};

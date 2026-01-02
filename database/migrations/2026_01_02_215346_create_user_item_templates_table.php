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
        Schema::create('user_item_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
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
            $table->unsignedInteger('usage_count')->default(1);
            $table->timestamp('last_used_at');
            $table->timestamps();

            // Prevent duplicate templates per user
            $table->unique(['user_id', 'name']);

            // Query performance for autocomplete
            $table->index(['user_id', 'usage_count', 'last_used_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_item_templates');
    }
};

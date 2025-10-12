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
        Schema::create('grocery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grocery_list_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('quantity', 10, 3)->nullable();
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
            ])->default('other');
            $table->enum('source_type', ['generated', 'manual'])->default('manual');
            $table->json('original_values')->nullable();
            $table->boolean('purchased')->default(false);
            $table->timestamp('purchased_at')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->softDeletes();
            $table->timestamps();

            // Indexes
            $table->index(['grocery_list_id', 'category', 'sort_order']);
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grocery_items');
    }
};

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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('prep_time')->nullable();
            $table->unsignedInteger('cook_time')->nullable();
            $table->unsignedInteger('servings')->default(4);
            $table->enum('meal_type', ['breakfast', 'lunch', 'dinner', 'snack'])->nullable();
            $table->string('cuisine', 100)->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->nullable();
            $table->json('dietary_tags')->nullable();
            $table->text('instructions');
            $table->string('image_url')->nullable();
            $table->timestamps();

            // Indexes
            $table->fullText(['name', 'description']);
            $table->index('meal_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};

<?php

namespace App\Models;

use App\Enums\MealType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealAssignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'meal_plan_id',
        'recipe_id',
        'date',
        'meal_type',
        'serving_multiplier',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'meal_type' => MealType::class,
        'serving_multiplier' => 'decimal:2',
    ];

    // Relationships

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}

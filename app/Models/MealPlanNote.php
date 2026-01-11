<?php

namespace App\Models;

use App\Enums\MealType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MealPlanNote extends Model
{
    /** @use HasFactory<\Database\Factories\MealPlanNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'meal_plan_id',
        'date',
        'meal_type',
        'title',
        'details',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'meal_type' => MealType::class,
        ];
    }

    // Relationships

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }
}

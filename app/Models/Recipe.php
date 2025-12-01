<?php

namespace App\Models;

use App\Enums\MealType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'prep_time',
        'cook_time',
        'servings',
        'meal_type',
        'cuisine',
        'difficulty',
        'dietary_tags',
        'instructions',
        'image_url',
        'source_url',
    ];

    protected $casts = [
        'dietary_tags' => 'array',
        'meal_type' => MealType::class,
        'prep_time' => 'integer',
        'cook_time' => 'integer',
        'servings' => 'integer',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function ingredients(): BelongsToMany
    {
        return $this->belongsToMany(Ingredient::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'sort_order', 'notes')
            ->withTimestamps()
            ->orderBy('recipe_ingredients.sort_order');
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class)->orderBy('sort_order');
    }

    public function mealAssignments(): HasMany
    {
        return $this->hasMany(MealAssignment::class);
    }

    // Scopes

    public function scopeImported($query)
    {
        return $query->whereNotNull('source_url');
    }

    public function scopeManual($query)
    {
        return $query->whereNull('source_url');
    }

    // Computed Attributes

    public function getTotalTimeAttribute(): ?int
    {
        if ($this->prep_time === null && $this->cook_time === null) {
            return null;
        }

        return ($this->prep_time ?? 0) + ($this->cook_time ?? 0);
    }

    public function getIsSystemRecipeAttribute(): bool
    {
        return $this->user_id === null;
    }

    public function getIngredientCountAttribute(): int
    {
        return $this->recipeIngredients()->count();
    }
}

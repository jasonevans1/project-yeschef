<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ingredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
    ];

    protected $casts = [
        'category' => IngredientCategory::class,
    ];

    // Relationships

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'recipe_ingredients')
            ->withPivot('quantity', 'unit', 'sort_order', 'notes')
            ->withTimestamps();
    }

    public function recipeIngredients(): HasMany
    {
        return $this->hasMany(RecipeIngredient::class);
    }

    // Mutators & Accessors

    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = strtolower(trim($value));
    }

    public function getNameAttribute(string $value): string
    {
        return ucfirst($value);
    }
}

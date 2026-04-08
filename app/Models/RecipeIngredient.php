<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
use App\Services\QuantityFormatter;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RecipeIngredient extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'ingredient_id',
        'quantity',
        'unit',
        'sort_order',
        'notes',
    ];

    protected $casts = [
        'unit' => MeasurementUnit::class,
        'quantity' => 'decimal:3',
        'sort_order' => 'integer',
    ];

    // Accessors

    /**
     * Format quantity for display as a human-readable fraction string.
     *
     * Examples:
     * - 2.000 → "2"
     * - 1.500 → "1½"
     * - 0.333 → "⅓"
     * - null → null
     */
    public function getDisplayQuantityAttribute(): ?string
    {
        return QuantityFormatter::format($this->quantity === null ? null : (float) $this->quantity);
    }

    // Relationships

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function ingredient(): BelongsTo
    {
        return $this->belongsTo(Ingredient::class);
    }
}

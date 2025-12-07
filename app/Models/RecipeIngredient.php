<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
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
     * Format quantity for display without trailing zeros.
     *
     * Examples:
     * - 2.000 → "2"
     * - 1.500 → "1.5"
     * - 0.333 → "0.333"
     * - null → null
     */
    public function getDisplayQuantityAttribute(): ?string
    {
        if ($this->quantity === null) {
            return null;
        }

        $formatted = number_format((float) $this->quantity, 3, '.', '');
        $formatted = rtrim($formatted, '0');
        $formatted = rtrim($formatted, '.');

        return $formatted;
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

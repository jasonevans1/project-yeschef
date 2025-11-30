<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use App\Enums\SourceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'grocery_list_id',
        'name',
        'quantity',
        'unit',
        'category',
        'source_type',
        'original_values',
        'purchased',
        'purchased_at',
        'notes',
        'sort_order',
    ];

    protected $casts = [
        'quantity' => 'decimal:3',
        'unit' => MeasurementUnit::class,
        'category' => IngredientCategory::class,
        'source_type' => SourceType::class,
        'original_values' => 'array',
        'purchased' => 'boolean',
        'purchased_at' => 'datetime',
        'sort_order' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // Relationships

    public function groceryList(): BelongsTo
    {
        return $this->belongsTo(GroceryList::class);
    }

    // Computed Attributes

    public function getIsGeneratedAttribute(): bool
    {
        return $this->source_type === SourceType::GENERATED;
    }

    public function getIsManualAttribute(): bool
    {
        return $this->source_type === SourceType::MANUAL;
    }

    public function getIsEditedAttribute(): bool
    {
        return $this->original_values !== null;
    }

    public function getDisplayQuantityAttribute(): string
    {
        if ($this->quantity === null) {
            return '';
        }

        $quantity = $this->quantity;
        $unit = $this->unit?->value ?? '';

        // Convert decimals to fractions for common measurements
        $fractional = $this->convertToFraction($quantity);

        return trim("{$fractional} {$unit}");
    }

    // Helper Methods

    private function convertToFraction(float $decimal): string
    {
        // Handle whole numbers
        if ($decimal == floor($decimal)) {
            return (string) (int) $decimal;
        }

        // Common fractions
        $fractions = [
            0.25 => '¼',
            0.33 => '⅓',
            0.5 => '½',
            0.66 => '⅔',
            0.75 => '¾',
        ];

        $whole = floor($decimal);
        $fractional = $decimal - $whole;

        // Find closest fraction
        foreach ($fractions as $value => $symbol) {
            if (abs($fractional - $value) < 0.05) {
                return $whole > 0 ? "{$whole}{$symbol}" : $symbol;
            }
        }

        // Default to decimal with 2 places
        return number_format($decimal, 2);
    }
}

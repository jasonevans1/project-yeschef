<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Model;

class CommonItemTemplate extends Model
{
    protected $fillable = [
        'name',
        'category',
        'unit',
        'default_quantity',
        'search_keywords',
        'usage_count',
    ];

    protected function casts(): array
    {
        return [
            'category' => IngredientCategory::class,
            'unit' => MeasurementUnit::class,
            'default_quantity' => 'float',
            'usage_count' => 'integer',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\IngredientCategory;
use App\Enums\MeasurementUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserItemTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'category',
        'unit',
        'default_quantity',
        'usage_count',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'category' => IngredientCategory::class,
            'unit' => MeasurementUnit::class,
            'default_quantity' => 'decimal:3',
            'usage_count' => 'integer',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

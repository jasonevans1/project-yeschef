<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class GroceryList extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'meal_plan_id',
        'name',
        'generated_at',
        'regenerated_at',
        'share_token',
        'share_expires_at',
    ];

    protected $casts = [
        'generated_at' => 'datetime',
        'regenerated_at' => 'datetime',
        'share_expires_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Cascade soft delete to grocery items
        static::deleting(function (GroceryList $groceryList) {
            $groceryList->groceryItems()->delete();
        });
    }

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealPlan(): BelongsTo
    {
        return $this->belongsTo(MealPlan::class);
    }

    public function groceryItems(): HasMany
    {
        return $this->hasMany(GroceryItem::class);
    }

    public function contentShares(): MorphMany
    {
        return $this->morphMany(ContentShare::class, 'shareable');
    }

    // Scopes

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        return $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
                ->orWhereHas('contentShares', function ($sq) use ($user) {
                    $sq->where('recipient_id', $user->id);
                })
                ->orWhereIn('user_id', function ($sq) use ($user) {
                    $sq->select('owner_id')
                        ->from('content_shares')
                        ->where('recipient_id', $user->id)
                        ->where('share_all', true)
                        ->where('shareable_type', self::class);
                });
        });
    }

    // Computed Attributes

    public function getIsStandaloneAttribute(): bool
    {
        return $this->meal_plan_id === null;
    }

    public function getIsMealPlanLinkedAttribute(): bool
    {
        return $this->meal_plan_id !== null;
    }

    public function getIsSharedAttribute(): bool
    {
        return $this->share_token !== null;
    }

    public function getShareUrlAttribute(): ?string
    {
        if (! $this->is_shared) {
            return null;
        }

        return route('grocery-lists.shared', $this->share_token);
    }

    public function getTotalItemsAttribute(): int
    {
        // Use eager loaded count if available to prevent N+1 queries
        if (isset($this->attributes['total_items'])) {
            return $this->attributes['total_items'];
        }

        return $this->groceryItems()->count();
    }

    public function getCompletedItemsAttribute(): int
    {
        // Use eager loaded count if available to prevent N+1 queries
        if (isset($this->attributes['completed_items'])) {
            return $this->attributes['completed_items'];
        }

        return $this->groceryItems()->where('purchased', true)->count();
    }

    public function getCompletionPercentageAttribute(): float
    {
        if ($this->total_items === 0) {
            return 0;
        }

        return round(($this->completed_items / $this->total_items) * 100, 2);
    }
}

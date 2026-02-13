<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MealPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'start_date',
        'end_date',
        'description',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function mealAssignments(): HasMany
    {
        return $this->hasMany(MealAssignment::class);
    }

    public function mealPlanNotes(): HasMany
    {
        return $this->hasMany(MealPlanNote::class);
    }

    public function groceryList(): HasOne
    {
        return $this->hasOne(GroceryList::class);
    }

    public function contentShares(): MorphMany
    {
        return $this->morphMany(ContentShare::class, 'shareable');
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'meal_assignments')
            ->withPivot('date', 'meal_type', 'serving_multiplier', 'notes')
            ->withTimestamps();
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

    public function getDurationDaysAttribute(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    public function getIsActiveAttribute(): bool
    {
        $today = now()->startOfDay();

        return $this->start_date <= $today && $this->end_date >= $today;
    }

    public function getIsPastAttribute(): bool
    {
        return $this->end_date < now()->startOfDay();
    }

    public function getIsFutureAttribute(): bool
    {
        return $this->start_date > now()->startOfDay();
    }

    public function getAssignmentCountAttribute(): int
    {
        // Use eager loaded count if available to prevent N+1 queries
        return $this->meal_assignments_count ?? $this->mealAssignments()->count();
    }
}

<?php

namespace App\Models;

use App\Enums\SharePermission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ContentShare extends Model
{
    /** @use HasFactory<\Database\Factories\ContentShareFactory> */
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'recipient_id',
        'recipient_email',
        'shareable_type',
        'shareable_id',
        'permission',
        'share_all',
    ];

    protected function casts(): array
    {
        return [
            'permission' => SharePermission::class,
            'share_all' => 'boolean',
        ];
    }

    // Relationships

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function shareable(): MorphTo
    {
        return $this->morphTo();
    }

    // Computed Attributes

    public function getIsPendingAttribute(): bool
    {
        return $this->recipient_id === null;
    }

    public function getIsShareAllAttribute(): bool
    {
        return $this->share_all === true;
    }
}

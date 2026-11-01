<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'stage_id', 'subject_id', 'starts_at', 'expires_at',
        'revoked_at', 'activated_by', 'amount', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'revoked_at' => 'datetime',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function activator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'activated_by');
    }

    public function scopeCurrentlyActive(Builder $query): Builder
    {
        $now = now();

        return $query->whereNull('revoked_at')
            ->where('starts_at', '<=', $now)
            ->where('expires_at', '>', $now);
    }

    public function isCurrentlyActive(): bool
    {
        return $this->revoked_at === null
            && $this->starts_at->lte(now())
            && $this->expires_at->gt(now());
    }

    public function getScopeLabelAttribute(): string
    {
        if ($this->stage_id) {
            return 'المرحلة: '.($this->stage?->name ?? '—');
        }

        return 'المادة: '.($this->subject?->full_name ?? '—');
    }
}

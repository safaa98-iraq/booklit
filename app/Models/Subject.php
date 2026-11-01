<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'stage_id', 'name', 'description', 'cover_image', 'color',
        'is_ministerial', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_ministerial' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /** Paid content: the subject itself or its whole stage is ministerial. */
    public function requiresSubscription(): bool
    {
        return $this->is_ministerial || (bool) $this->stage?->is_ministerial;
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Stage::class);
    }

    public function booklets(): HasMany
    {
        return $this->hasMany(Booklet::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function editors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'editor_subject')->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /** Restrict to the subjects a staff user can manage. */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('editors', fn (Builder $q) => $q->whereKey($user->getKey()));
    }

    public function getFullNameAttribute(): string
    {
        return $this->stage ? "{$this->stage->name} — {$this->name}" : $this->name;
    }
}

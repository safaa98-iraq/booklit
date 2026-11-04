<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'is_active',
        'last_login_at',
        'preferences',
    ];

    /** Student preferences and their defaults. */
    public const PREFERENCE_DEFAULTS = [
        'font_size' => 18,          // reader text size (px)
        'notifications' => true,    // show notifications on the home page
        'language' => 'ar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
            'preferences' => 'array',
        ];
    }

    /* ----------------------------------------------------------------- Roles */

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isEditor(): bool
    {
        return $this->role === UserRole::Editor;
    }

    public function isStudent(): bool
    {
        return $this->role === UserRole::Student;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active && in_array($this->role, [UserRole::Admin, UserRole::Editor], true);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    /** Whether this staff member may manage content of the given subject. */
    public function canManageSubject(Subject|int $subject): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if (! $this->isEditor()) {
            return false;
        }

        $subjectId = $subject instanceof Subject ? $subject->getKey() : $subject;

        return $this->assignedSubjects()->whereKey($subjectId)->exists();
    }

    /** @return list<int> */
    public function managedSubjectIds(): array
    {
        return $this->assignedSubjects()->pluck('subjects.id')->all();
    }

    public function preference(string $key): mixed
    {
        return ($this->preferences ?? [])[$key] ?? self::PREFERENCE_DEFAULTS[$key] ?? null;
    }

    /* ------------------------------------------------------------- Relations */

    /** Subjects an editor is allowed to author content for. */
    public function assignedSubjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'editor_subject')->withTimestamps();
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function activeDevice(): HasOne
    {
        return $this->hasOne(Device::class)->whereNull('released_at')->latestOfMany('bound_at');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(Attempt::class);
    }

    public function topicProgress(): HasMany
    {
        return $this->hasMany(TopicProgress::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    public function highlights(): HasMany
    {
        return $this->hasMany(UserHighlight::class);
    }

    /* ---------------------------------------------------------------- Scopes */

    public function scopeStudents(Builder $query): Builder
    {
        return $query->where('role', UserRole::Student);
    }

    public function scopeEditors(Builder $query): Builder
    {
        return $query->where('role', UserRole::Editor);
    }
}

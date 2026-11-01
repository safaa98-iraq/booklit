<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'device_name', 'platform', 'ip_address',
        'bound_at', 'last_seen_at', 'released_at', 'released_by',
    ];

    protected function casts(): array
    {
        return [
            'bound_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->released_at === null;
    }
}

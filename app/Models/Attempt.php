<?php

namespace App\Models;

use App\Enums\AttemptMode;
use App\Enums\AttemptStatus;
use App\Enums\Difficulty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'subject_id', 'booklet_id', 'mode', 'difficulty', 'status',
        'total_questions', 'correct_count', 'score', 'started_at', 'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'mode' => AttemptMode::class,
            'difficulty' => Difficulty::class,
            'status' => AttemptStatus::class,
            'score' => 'float',
            'started_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function isSubmitted(): bool
    {
        return $this->status === AttemptStatus::Submitted;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function booklet(): BelongsTo
    {
        return $this->belongsTo(Booklet::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class)->orderBy('position');
    }
}

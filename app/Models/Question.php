<?php

namespace App\Models;

use App\Enums\Difficulty;
use App\Enums\QuestionLinkType;
use App\Enums\QuestionSource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Question extends Model
{
    use HasFactory;

    public const MIN_OPTIONS = 2;

    public const MAX_OPTIONS = 6;

    protected $fillable = [
        'subject_id', 'link_type', 'booklet_id', 'topic_id',
        'source', 'source_reference', 'difficulty',
        'text', 'image', 'explanation', 'is_active',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'link_type' => QuestionLinkType::class,
            'source' => QuestionSource::class,
            'difficulty' => Difficulty::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // Keep the foreign keys consistent with the chosen link type.
        static::saving(function (Question $question) {
            switch ($question->link_type) {
                case QuestionLinkType::Topic:
                    if ($question->topic_id) {
                        $topic = Topic::with('booklet')->find($question->topic_id);
                        $question->booklet_id = $topic?->booklet_id;
                        $question->subject_id = $topic?->booklet?->subject_id ?? $question->subject_id;
                    }
                    break;
                case QuestionLinkType::Booklet:
                    $question->topic_id = null;
                    if ($question->booklet_id) {
                        $question->subject_id = Booklet::whereKey($question->booklet_id)->value('subject_id') ?? $question->subject_id;
                    }
                    break;
                default:
                    $question->link_type = QuestionLinkType::None;
                    $question->topic_id = null;
                    $question->booklet_id = null;
            }
        });

        // Drop passage anchors that no longer belong to the linked booklet.
        static::saved(function (Question $question) {
            if (! $question->wasChanged(['link_type', 'booklet_id'])) {
                return;
            }

            $question->anchors()
                ->when(
                    $question->booklet_id,
                    fn ($q) => $q->whereHas('block.topic', fn ($t) => $t->where('booklet_id', '!=', $question->booklet_id)),
                )
                ->delete();
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function booklet(): BelongsTo
    {
        return $this->belongsTo(Booklet::class);
    }

    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('sort_order')->orderBy('id');
    }

    public function correctOption(): HasOne
    {
        return $this->hasOne(QuestionOption::class)->where('is_correct', true);
    }

    public function anchors(): HasMany
    {
        return $this->hasMany(PassageAnchor::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(AttemptAnswer::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereIn('subject_id', $user->managedSubjectIds());
    }
}

<?php

namespace App\Models;

use App\Enums\Category;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Entry extends Model
{
    use HasFactory;

    protected $fillable = ['spent_on', 'category', 'description', 'amount', 'note'];

    protected function casts(): array
    {
        return [
            'spent_on' => 'date',
            'category' => Category::class,
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->oldest();
    }

    /** Week of the month (1–5), as kakeibo pages are split by week. */
    public function week(): int
    {
        return intdiv($this->spent_on->day - 1, 7) + 1;
    }
}

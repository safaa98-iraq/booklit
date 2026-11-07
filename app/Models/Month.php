<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Month extends Model
{
    use HasFactory;

    protected $fillable = [
        'year', 'month', 'income', 'fixed_costs', 'savings_goal',
        'intention', 'reflection', 'improvement',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'month' => 'integer',
            'income' => 'decimal:2',
            'fixed_costs' => 'decimal:2',
            'savings_goal' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function start(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1)->startOfDay();
    }

    public function end(): Carbon
    {
        return $this->start()->endOfMonth();
    }

    /** Money left to spend on the four pillars once fixed costs and savings are set aside. */
    public function available(): float
    {
        return (float) $this->income - (float) $this->fixed_costs - (float) $this->savings_goal;
    }

    public function key(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    /** Entries of this month (belonging to the same user). */
    public function entries(): Builder
    {
        return Entry::query()
            ->where('user_id', $this->user_id)
            ->whereBetween('spent_on', [$this->start()->toDateString(), $this->end()->toDateString()]);
    }
}

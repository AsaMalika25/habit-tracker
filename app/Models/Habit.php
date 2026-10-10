<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Habit extends Model
{
    use HasFactory;

    protected $attributes = [
        'category' => 'mindfulness',
        'is_active' => true,
    ];

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'start_date',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /** @param Builder<Habit> $query */
    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** @param Builder<Habit> $query */
    public function scopeForDate(Builder $query, string $date): void
    {
        $query->where(fn (Builder $dateQuery) => $dateQuery
            ->whereDate('start_date', $date)
            ->orWhere(fn (Builder $legacyQuery) => $legacyQuery
                ->whereNull('start_date')
                ->whereDate('created_at', $date)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(HabitLog::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'entry_date',
        'title',
        'content',
        'mood_score',
        'mood',
        'tags',
        'image_url',
        'audio_url',
        'is_favorite',
        'is_bookmarked',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'mood_score' => 'integer',
            'tags' => 'array',
            'is_favorite' => 'boolean',
            'is_bookmarked' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

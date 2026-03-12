<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    protected $fillable = ['room_id', 'current_round', 'status', 'started_at', 'start_timer_ends_at', 'question_started_at', 'question_ids'];

    protected $casts = [
        'started_at' => 'datetime',
        'start_timer_ends_at' => 'datetime',
        'question_started_at' => 'datetime',
        'question_ids' => 'array',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function sessionAnswers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class, 'game_session_id');
    }
}

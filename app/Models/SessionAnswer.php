<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAnswer extends Model
{
    protected $fillable = [
        'game_session_id',
        'question_id',
        'custom_question_id',
        'room_player_id',
        'answer_index',
        'correct',
        'score_delta',
    ];

    protected $casts = [
        'correct' => 'boolean',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function customQuestion(): BelongsTo
    {
        return $this->belongsTo(CustomQuestion::class);
    }

    public function roomPlayer(): BelongsTo
    {
        return $this->belongsTo(RoomPlayer::class);
    }
}

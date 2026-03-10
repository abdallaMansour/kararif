<?php

namespace App\Models;

use App\Models\Adventurer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomPlayer extends Model
{
    protected $fillable = ['room_id', 'user_id', 'adventurer_id', 'team_id', 'is_leader', 'score'];

    protected $casts = [
        'joined_at' => 'datetime',
        'is_leader' => 'boolean',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adventurer(): BelongsTo
    {
        return $this->belongsTo(Adventurer::class);
    }

    public function resolvedUser(): ?object
    {
        return $this->adventurer ?? $this->user;
    }

    public function sessionAnswers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class, 'room_player_id');
    }
}

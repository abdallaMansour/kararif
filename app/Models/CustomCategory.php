<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class CustomCategory extends Model
{
    protected $fillable = [
        'owner_user_id',
        'owner_adventurer_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function ownerAdventurer(): BelongsTo
    {
        return $this->belongsTo(Adventurer::class, 'owner_adventurer_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CustomQuestion::class, 'custom_category_id');
    }

    /**
     * Finished game sessions played in a custom room for this category (usage count).
     */
    public function finishedCustomSessions(): HasManyThrough
    {
        return $this->hasManyThrough(
            GameSession::class,
            Room::class,
            'custom_category_id',
            'room_id',
            'id',
            'id'
        )->where('rooms.is_custom', true)
            ->where('game_sessions.status', 'finished');
    }

    public function scopeOwnedBy($query, $authUser)
    {
        if ($authUser instanceof Adventurer) {
            return $query->where('owner_adventurer_id', $authUser->id);
        }

        return $query->where('owner_user_id', $authUser->id);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
    protected $fillable = [
        'code',
        'type_id',
        'category_id',
        'subcategory_id',
        'created_by',
        'title',
        'rounds',
        'teams',
        'players',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function roomPlayers(): HasMany
    {
        return $this->hasMany(RoomPlayer::class, 'room_id');
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class, 'room_id');
    }
}

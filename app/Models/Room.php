<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Room extends Model
{
    protected $fillable = [
        'code',
        'is_custom',
        'type_id',
        'created_by_adventurer_id',
        'category_id',
        'subcategory_id',
        'custom_category_id',
        'custom_stage_id',
        'created_by',
        'title',
        'rounds',
        'questions_count',
        'life_points',
        'teams',
        'players',
        'status',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_custom' => 'boolean',
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

    public function customCategory(): BelongsTo
    {
        return $this->belongsTo(CustomCategory::class, 'custom_category_id');
    }

    public function customStage(): BelongsTo
    {
        return $this->belongsTo(CustomStage::class, 'custom_stage_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function creatorAdventurer(): BelongsTo
    {
        return $this->belongsTo(Adventurer::class, 'created_by_adventurer_id');
    }

    public function roomPlayers(): HasMany
    {
        return $this->hasMany(RoomPlayer::class, 'room_id');
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class, 'room_id');
    }

    public function tvDisplay(): HasOne
    {
        return $this->hasOne(TvDisplay::class);
    }
}

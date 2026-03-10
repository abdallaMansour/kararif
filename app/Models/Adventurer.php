<?php

namespace App\Models;

use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class Adventurer extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name',
        'username',
        'country',
        'email',
        'phone',
        'password',
        'pin_code',
        'avatar_id',
        'lifetime_score',
        'number_correct_answers',
        'number_wrong_answers',
        'number_full_winnings',
        'number_surrender_times',
        'points',
        'surrender_count',
        'country_label',
        'country_code',
        'available_sessions',
        'rank_discount_percent',
        'rank_discount_uses_left',
        'granted_discount_rank_ids',
        'granted_session_rank_ids',
    ];

    protected $hidden = [
        'password',
        'pin_code',
        'remember_token',
    ];

    protected $casts = [
        'lifetime_score' => 'decimal:2',
        'number_correct_answers' => 'integer',
        'number_wrong_answers' => 'integer',
        'number_full_winnings' => 'integer',
        'number_surrender_times' => 'integer',
        'points' => 'integer',
        'surrender_count' => 'integer',
        'available_sessions' => 'integer',
        'granted_discount_rank_ids' => 'array',
        'granted_session_rank_ids' => 'array',
    ];

    public function avatarRelation(): BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'avatar_id');
    }

    public function roomPlayers(): HasMany
    {
        return $this->hasMany(RoomPlayer::class, 'adventurer_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'adventurer_id');
    }

    public function couponUsages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'adventurer_id');
    }

    public function getFirstMediaUrl(): ?string
    {
        return null;
    }
}

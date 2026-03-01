<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Laratrust\Contracts\LaratrustUser;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Laratrust\Traits\HasRolesAndPermissions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements LaratrustUser, HasMedia
{
    use HasApiTokens, HasFactory, Notifiable, HasRolesAndPermissions, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
        'email',
        'password',
        'balance',
        'level',
        'points',
        'avatar',
        'surrender_count',
        'country_label',
        'country_code',
        'avatar_id',
        'available_sessions',
        'rank_discount_percent',
        'rank_discount_uses_left',
        'granted_discount_rank_ids',
        'granted_session_rank_ids',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'surrender_count' => 'integer',
        'available_sessions' => 'integer',
        'granted_discount_rank_ids' => 'array',
        'granted_session_rank_ids' => 'array',
    ];

    public function roomPlayers(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RoomPlayer::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function avatarRelation(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Avatar::class, 'avatar_id');
    }

    public function couponUsages(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }
}

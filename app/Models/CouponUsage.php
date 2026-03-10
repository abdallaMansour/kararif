<?php

namespace App\Models;

use App\Models\Adventurer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CouponUsage extends Model
{
    protected $fillable = ['user_id', 'adventurer_id', 'coupon_id', 'used_at'];

    protected $casts = [
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function adventurer(): BelongsTo
    {
        return $this->belongsTo(Adventurer::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}

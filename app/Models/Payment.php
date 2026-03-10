<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Adventurer;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'adventurer_id',
        'payment_package_id',
        'coupon_id',
        'payment_id',
        'status',
        'amount',
    ];

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

    public function paymentPackage(): BelongsTo
    {
        return $this->belongsTo(PaymentPackage::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopOrder extends Model
{
    public const STATUS_PENDING_PAYMENT = 'pending_payment';
    public const STATUS_NEW_ORDER = 'new_order';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ON_DELIVERY = 'on_delivery';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'order_number',
        'status',
        'customer_full_name',
        'customer_phone',
        'customer_email',
        'delivery_emirate',
        'delivery_area',
        'delivery_detail',
        'subtotal_aed',
        'shipping_fee_aed',
        'total_aed',
        'gateway_name',
        'gateway_payment_intent_id',
        'gateway_reference',
        'paid_at',
    ];

    protected $casts = [
        'subtotal_aed' => 'float',
        'shipping_fee_aed' => 'float',
        'total_aed' => 'float',
        'paid_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}

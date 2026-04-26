<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopOrderItem extends Model
{
    protected $fillable = [
        'shop_order_id',
        'shop_product_id',
        'quantity',
        'unit_price_aed',
        'line_total_aed',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'unit_price_aed' => 'float',
        'line_total_aed' => 'float',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(ShopOrder::class, 'shop_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(ShopProduct::class, 'shop_product_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopProduct extends Model
{
    protected $fillable = [
        'sku',
        'name_ar',
        'price_aed',
        'image_url',
        'is_active',
        'is_sellable',
    ];

    protected $casts = [
        'price_aed' => 'float',
        'is_active' => 'boolean',
        'is_sellable' => 'boolean',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(ShopOrderItem::class);
    }
}

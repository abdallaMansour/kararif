<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ShopProduct extends Model implements HasMedia
{
    use InteractsWithMedia;

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

    public function resolvedImageUrl(): ?string
    {
        $mediaUrl = $this->getFirstMediaUrl();
        if (! empty($mediaUrl)) {
            return $mediaUrl;
        }

        return $this->image_url;
    }
}

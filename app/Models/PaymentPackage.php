<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentPackage extends Model
{
    protected $fillable = ['name', 'points', 'price', 'currency', 'active'];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'payment_package_id');
    }
}

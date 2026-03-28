<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomCategory extends Model
{
    protected $fillable = [
        'owner_user_id',
        'owner_adventurer_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
        'usage_count' => 'integer',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function ownerAdventurer(): BelongsTo
    {
        return $this->belongsTo(Adventurer::class, 'owner_adventurer_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(CustomQuestion::class, 'custom_category_id');
    }

    public function scopeOwnedBy($query, $authUser)
    {
        if ($authUser instanceof Adventurer) {
            return $query->where('owner_adventurer_id', $authUser->id);
        }

        return $query->where('owner_user_id', $authUser->id);
    }
}

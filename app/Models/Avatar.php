<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Avatar extends Model
{
    protected $fillable = ['name', 'image'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'avatar_id');
    }
}

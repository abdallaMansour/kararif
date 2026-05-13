<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreatorSubcategoryStageTail extends Model
{
    protected $fillable = [
        'subcategory_id',
        'creator_owner_type',
        'creator_owner_id',
        'last_round_stage_type',
    ];

    public function subcategory(): BelongsTo
    {
        return $this->belongsTo(Subcategory::class);
    }
}

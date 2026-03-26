<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomQuestion extends Model
{
    public const KIND_NORMAL = 'normal';

    protected $fillable = [
        'owner_user_id',
        'owner_adventurer_id',
        'custom_category_id',
        'name',
        'question_kind',
        'answer_1',
        'is_correct_1',
        'answer_2',
        'is_correct_2',
        'answer_3',
        'is_correct_3',
        'answer_4',
        'is_correct_4',
        'status',
    ];

    protected $casts = [
        'is_correct_1' => 'boolean',
        'is_correct_2' => 'boolean',
        'is_correct_3' => 'boolean',
        'is_correct_4' => 'boolean',
        'status' => 'boolean',
    ];

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function ownerAdventurer(): BelongsTo
    {
        return $this->belongsTo(Adventurer::class, 'owner_adventurer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(CustomCategory::class, 'custom_category_id');
    }

    public function scopeOwnedBy($query, $authUser)
    {
        if ($authUser instanceof Adventurer) {
            return $query->where('owner_adventurer_id', $authUser->id);
        }

        return $query->where('owner_user_id', $authUser->id);
    }
}

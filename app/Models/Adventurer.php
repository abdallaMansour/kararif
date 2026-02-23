<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adventurer extends Model
{
    protected $fillable = [
        'name',
        'country',
        'email',
        'pin_code',
        'lifetime_score',
        'number_correct_answers',
        'number_wrong_answers',
        'number_full_winnings',
        'number_surrender_times',
    ];

    protected $hidden = [
        'pin_code',
    ];

    protected $casts = [
        'lifetime_score' => 'decimal:2',
        'number_correct_answers' => 'integer',
        'number_wrong_answers' => 'integer',
        'number_full_winnings' => 'integer',
        'number_surrender_times' => 'integer',
    ];
}

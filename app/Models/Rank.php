<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Rank extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'start_score',
    ];
}

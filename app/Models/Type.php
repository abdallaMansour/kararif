<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Type extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}

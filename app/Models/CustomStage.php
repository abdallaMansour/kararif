<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class CustomStage extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const TYPE_LIFE_POINTS = 'life_points';

    protected $fillable = [
        'name',
        'life_points_per_question',
        'number_of_questions',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function rooms()
    {
        return $this->hasMany(Room::class, 'custom_stage_id');
    }
}

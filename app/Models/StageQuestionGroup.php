<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class StageQuestionGroup extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'stage_id',
        'sort_order',
    ];

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }
}

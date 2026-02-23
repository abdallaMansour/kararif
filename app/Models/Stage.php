<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Stage extends Model implements HasMedia
{
    use InteractsWithMedia;

    const TYPE_QUESTIONS_GROUP = 'questions_group';
    const TYPE_LIFE_POINTS = 'life_points';

    protected $fillable = [
        'name',
        'stage_type',
        'question_groups_count',
        'number_of_questions',
        'life_points_per_question',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function questionGroups()
    {
        return $this->hasMany(StageQuestionGroup::class, 'stage_id');
    }
}

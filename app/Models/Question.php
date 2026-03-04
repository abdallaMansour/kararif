<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Question extends Model implements HasMedia
{
    use InteractsWithMedia;
    public const KIND_NORMAL = 'normal';
    public const KIND_WORDS = 'words';
    public const KIND_VOICE = 'voice';
    public const KIND_VIDEO = 'video';
    public const KIND_IMAGE = 'image';

    protected $fillable = [
        'type_id',
        'category_id',
        'subcategory_id',
        'name',
        'question_kind',
        'word_data',
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
        'word_data' => 'array',
        'is_correct_1' => 'boolean',
        'is_correct_2' => 'boolean',
        'is_correct_3' => 'boolean',
        'is_correct_4' => 'boolean',
        'status' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }
}

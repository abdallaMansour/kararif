<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    protected $fillable = [
        'stage_id',
        'category_id',
        'subcategory_id',
        'type_id',
        'name',
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

    public function stage()
    {
        return $this->belongsTo(Stage::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function subcategory()
    {
        return $this->belongsTo(Subcategory::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class);
    }
}

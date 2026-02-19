<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Type extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'stage_id',
        'category_id',
        'subcategory_id',
        'name',
        'monthly_price',
        'yearly_price',
        'status',
    ];

    protected $casts = [
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

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}

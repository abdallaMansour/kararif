<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;

class Category extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'type_id',
        'name',
        'status',
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function type()
    {
        return $this->belongsTo(Type::class);
    }

    public function subcategories()
    {
        return $this->hasMany(Subcategory::class, 'category_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }
}

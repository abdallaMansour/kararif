<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Astrotomic\Translatable\Translatable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Astrotomic\Translatable\Contracts\Translatable as TranslatableContract;


class Seo extends Model implements HasMedia, TranslatableContract
{
    use Translatable,
        InteractsWithMedia,
        HasFactory;

    protected $table = 'seo';

    /**
     * The attributes that are mass assignable.
     */
    protected $translatedAttributes = [
        'title',
        'description',
        'site_name',
        'keyword',
    ];

    /**
     * @var array
     */
    protected $fillable = ['name_id'];

    protected $with = ['translations', 'media'];

    // protected static function newFactory(): SeoFactory
    // {
    //     return SeoFactory::new();
    // }

}

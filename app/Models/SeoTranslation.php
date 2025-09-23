<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Seo\Database\factories\SeoFactoryTranslations;

class SeoTranslation extends Model
{
    use HasFactory;
    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
        'site_name',
        'keyword',
    ];

    // protected static function newFactory(): SeoFactoryTranslations
    // {
    //     return SeoFactoryTranslations::new();
    // }
}

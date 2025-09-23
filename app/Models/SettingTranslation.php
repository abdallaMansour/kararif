<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingTranslation extends Model
{
    use HasFactory;

    /**
     * @var bool
     */
    public $timestamps = false;

    protected $fillable = [
        'setting_id',
        'locale',
        'address',
        'footer_description'
    ];

    public function setting()
    {
        return $this->belongsTo(Setting::class);
    }
}

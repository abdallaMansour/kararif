<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['display_name', 'locale', 'role_id'];

    public $timestamps = false;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }
}

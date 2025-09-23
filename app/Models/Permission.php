<?php

namespace App\Models;

use Laratrust\Models\Permission as PermissionModel;

class Permission extends PermissionModel
{
    public $guarded = [];
    public function translations()
    {
        return $this->hasMany(PermissionTranslation::class);
    }
}

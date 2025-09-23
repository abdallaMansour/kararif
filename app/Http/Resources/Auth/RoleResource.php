<?php

namespace App\Http\Resources\Auth;

use App\Http\Resources\Auth\PermissionResource;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name' => $this->display_name,
            'permissions' => PermissionResource::collection($this->permissions),
        ];
    }
}

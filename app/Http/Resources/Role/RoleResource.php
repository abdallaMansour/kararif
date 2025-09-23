<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */

    public function toArray(Request $request): array
    {
        $translation = $this->translations()->get();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'translate_display_name_en' => $translation->where('locale', 'en')->first()->display_name ?? null,
            'translate_display_name_ar' => $translation->where('locale', 'ar')->first()->display_name ?? null,
            'permissions' => PermissionResource::collection($this->permissions),
        ];
    }
}

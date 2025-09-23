<?php

namespace App\Http\Resources\Role;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $translations = $this->translations;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'display_name_ar' => $translations->where('locale', 'ar')->first()->display_name ?? null,
            'display_name_en' => $translations->where('locale', 'en')->first()->display_name ?? null,
        ];
    }
}

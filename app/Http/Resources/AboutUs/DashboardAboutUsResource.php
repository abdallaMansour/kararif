<?php

namespace App\Http\Resources\AboutUs;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardAboutUsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'description_ar' => $this->description_ar,
            'description_en' => $this->description_en,
        ];
    }
}

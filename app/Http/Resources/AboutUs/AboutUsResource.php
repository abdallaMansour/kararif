<?php

namespace App\Http\Resources\AboutUs;

use Illuminate\Http\Resources\Json\JsonResource;

class AboutUsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $description = 'description_' . app()->getLocale();

        return [
            'id' => $this->id,
            'description' => $this->$description,
        ];
    }
}

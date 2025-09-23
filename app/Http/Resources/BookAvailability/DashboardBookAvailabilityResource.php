<?php

namespace App\Http\Resources\BookAvailability;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardBookAvailabilityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'link' => $this->link,
            'country' => $this->country,
            'image' => $this->getFirstMediaUrl(),
        ];
    }
}

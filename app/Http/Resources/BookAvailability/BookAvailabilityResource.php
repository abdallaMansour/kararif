<?php

namespace App\Http\Resources\BookAvailability;

use Illuminate\Http\Resources\Json\JsonResource;

class BookAvailabilityResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

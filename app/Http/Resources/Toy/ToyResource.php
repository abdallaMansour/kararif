<?php

namespace App\Http\Resources\Toy;

use Illuminate\Http\Resources\Json\JsonResource;

class ToyResource extends JsonResource
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
            'type' => $this->type,
            'link' => $this->link,
            'image' => $this->getFirstMediaUrl(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

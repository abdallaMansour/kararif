<?php

namespace App\Http\Resources\Author;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {

        return [
            'id' => $this->id,
            'name' => $this->author_name,
            'title' => $this->author_title,
            'description' => $this->author_description,
            'image' => $this->getFirstMediaUrl('author_image'),
        ];
    }
}

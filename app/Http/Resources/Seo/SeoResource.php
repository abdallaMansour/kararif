<?php

namespace App\Http\Resources\Seo;

use Illuminate\Http\Resources\Json\JsonResource;

class SeoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        $image = $this->getFirstMediaUrl('image');
        $icon = $this->getFirstMediaUrl('icon');

        return [
            'image' => $this->when($image, $image, 'https://placehold.co/65x65'),
            'icon' => $this->when($icon, $icon, 'https://placehold.co/65x65'),
            'title' => $this->title,
            'description' => $this->description,
            'keyword' => $this->keyword,
            'site_name' => $this->site_name,
        ];
    }
}

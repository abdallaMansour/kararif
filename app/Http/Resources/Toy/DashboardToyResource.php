<?php

namespace App\Http\Resources\Toy;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardToyResource extends JsonResource
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
            'audios' => $this->getMedia('audios')->map(function ($audio) {
                return $audio->getUrl();
            }),
            'videos' => $this->getMedia('videos')->map(function ($video) {
                return $video->getUrl();
            }),
        ];
    }
}

<?php

namespace App\Http\Resources\Seo;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSeoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {

        $image = $this->getFirstMediaUrl('image');
        $icon = $this->getFirstMediaUrl('icon');

        // $langs = $this->translations;

        // $data = [];
        // if ($this->name_id == 'home') {
        //     foreach ($langs as $lang) {
        //         $data[$lang->locale] = [
        //             'id' => $lang->id,
        //             'title' => $lang->title,
        //             'description' => $lang->description,
        //             'site_name' => $lang->site_name,
        //             'keyword' => $lang->keyword,
        //         ];
        //     }
        // } else {
        //     foreach ($langs as $lang) {
        //         $data[$lang->locale] = [
        //             'id' => $lang->id,
        //             'title' => $lang->title,
        //             'description' => $lang->description,
        //             'keyword' => $lang->keyword,
        //         ];
        //     }
        // }

        return [
            'name_id' => $this->name_id,
            'image' => $this->when($image, $image, 'https://placehold.co/65x65'),
            'icon' => $this->when($icon, $icon, 'https://placehold.co/65x65'),
            // 'data' => [
            'title' => $this->title,
            'description' => $this->description,
            'keyword' => $this->keyword,
            'site_name' => $this->site_name,
            // ]
        ];
    }
}

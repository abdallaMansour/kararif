<?php

namespace App\Repositories\Seo;

use App\Models\Seo;

class SeoRepository
{
    public function update($seo_id, array $data)
    {
        $seo = Seo::where('name_id', $seo_id)->first();

        $dataToUpdate = [
            'title:ar' => $data['title_ar'],
            'title:en' => $data['title_en'],
            'description:ar' => $data['description_ar'],
            'description:en' => $data['description_en'],
            'keyword:ar' => $data['keyword_ar'],
            'keyword:en' => $data['keyword_en'],
        ];

        // Add additional fields for 'home' type if necessary
        if ($seo_id == 'home') {
            $dataToUpdate['site_name:ar'] = $data['site_name_ar'];
            $dataToUpdate['site_name:en'] = $data['site_name_en'];
        }

        if ($seo)
            $seo->update($dataToUpdate);
        else
            Seo::create(array_merge(['name_id' => $seo_id], $dataToUpdate));


        if (isset($data['image'])) {

            $seo->clearMediaCollection('image');
            $seo->addMedia($data['image'])->toMediaCollection('image');
        }

        if (isset($data['icon'])) {

            $seo->clearMediaCollection('icon');
            $seo->addMedia($data['icon'])->toMediaCollection('icon');
        }
    }
}

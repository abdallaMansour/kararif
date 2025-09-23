<?php

namespace App\Http\Resources\Setting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'phone' => $this->phone,
            'email' => $this->email,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'linkedin' => $this->linkedin,
            'whatsapp' => $this->whatsapp,
            'x' => $this->x,
            'logo' => $this->getFirstMediaUrl(),
            'footer_description_en' => $this->getTranslation('en')->footer_description,
            'footer_description_ar' => $this->getTranslation('ar')->footer_description,
            'address_en' => $this->getTranslation('en')->address,
            'address_ar' => $this->getTranslation('ar')->address,
        ];
    }
}

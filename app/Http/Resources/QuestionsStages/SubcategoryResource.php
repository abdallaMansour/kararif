<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class SubcategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'stage_id' => $this->stage_id,
            'category_id' => $this->category_id,
            'name' => $this->name,
            'image' => $this->getFirstMediaUrl(),
            'monthly_price' => $this->monthly_price,
            'yearly_price' => $this->yearly_price,
            'status' => $this->status,
        ];
    }
}

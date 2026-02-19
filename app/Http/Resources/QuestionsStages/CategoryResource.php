<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'stage_id' => $this->stage_id,
            'name' => $this->name,
            'image' => $this->getFirstMediaUrl(),
            'monthly_price' => $this->monthly_price,
            'yearly_price' => $this->yearly_price,
            'status' => $this->status,
        ];
    }
}

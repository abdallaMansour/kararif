<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class TypeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'image' => $this->getFirstMediaUrl(),
            'status' => $this->status,
            'categories_count' => (int) ($this->categories_count ?? 0),
        ];
    }
}

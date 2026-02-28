<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->type_id,
            'type_name' => $this->type?->name,
            'name' => $this->name,
            'image' => $this->getFirstMediaUrl(),
            'status' => $this->status,
            'questions_count' => (int) ($this->questions_count ?? 0),
        ];
    }
}

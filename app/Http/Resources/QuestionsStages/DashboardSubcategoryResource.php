<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardSubcategoryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'name' => $this->name,
            'image' => $this->getFirstMediaUrl(),
            'status' => $this->status,
            'questions_count' => (int) ($this->questions_count ?? 0),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

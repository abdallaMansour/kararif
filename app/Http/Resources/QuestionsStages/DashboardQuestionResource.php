<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'stage_id' => $this->stage_id,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
            'type_id' => $this->type_id,
            'name' => $this->name,
            'answer_1' => $this->answer_1,
            'is_correct_1' => $this->is_correct_1,
            'answer_2' => $this->answer_2,
            'is_correct_2' => $this->is_correct_2,
            'answer_3' => $this->answer_3,
            'is_correct_3' => $this->is_correct_3,
            'answer_4' => $this->answer_4,
            'is_correct_4' => $this->is_correct_4,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

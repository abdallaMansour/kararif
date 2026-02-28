<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardQuestionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type_id' => $this->type_id,
            'type_name' => $this->type?->name,
            'category_id' => $this->category_id,
            'subcategory_id' => $this->subcategory_id,
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
            'start_video' => $this->getFirstMediaUrl('start_video'),
            'lunch_video' => $this->getFirstMediaUrl('lunch_video'),
            'question_video' => $this->getFirstMediaUrl('question_video'),
            'correct_answer_video' => $this->getFirstMediaUrl('correct_answer_video'),
            'wrong_answer_video' => $this->getFirstMediaUrl('wrong_answer_video'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

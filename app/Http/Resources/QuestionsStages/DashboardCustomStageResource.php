<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardCustomStageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'life_points_per_question' => $this->life_points_per_question !== null
                ? (float) $this->life_points_per_question
                : null,
            'number_of_questions' => $this->number_of_questions !== null
                ? (int) $this->number_of_questions
                : null,
            'status' => (bool) $this->status,
            'cover_image_url' => $this->getFirstMediaUrl('cover_image'),
            'start_video' => $this->getFirstMediaUrl('start_video'),
            'end_video' => $this->getFirstMediaUrl('end_video'),
            'lunch_video' => $this->getFirstMediaUrl('lunch_video'),
            'correct_answer_video' => $this->getFirstMediaUrl('correct_answer_video'),
            'wrong_answer_video' => $this->getFirstMediaUrl('wrong_answer_video'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

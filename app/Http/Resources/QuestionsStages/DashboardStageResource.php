<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'stage_type' => $this->stage_type,
            'question_groups_count' => $this->question_groups_count,
            'number_of_questions' => $this->number_of_questions,
            'life_points_per_question' => $this->life_points_per_question,
            'start_video' => $this->getFirstMediaUrl('start_video'),
            'end_video' => $this->getFirstMediaUrl('end_video'),
            'correct_answer_video' => $this->getFirstMediaUrl('correct_answer_video'),
            'wrong_answer_video' => $this->getFirstMediaUrl('wrong_answer_video'),
            'question_groups' => DashboardStageQuestionGroupResource::collection($this->whenLoaded('questionGroups')),
            'status' => $this->status,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

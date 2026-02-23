<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStageQuestionGroupResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'stage_id' => $this->stage_id,
            'sort_order' => $this->sort_order,
            'start_video' => $this->getFirstMediaUrl('start_video'),
            'end_video' => $this->getFirstMediaUrl('end_video'),
            'correct_answer_video' => $this->getFirstMediaUrl('correct_answer_video'),
            'wrong_answer_video' => $this->getFirstMediaUrl('wrong_answer_video'),
        ];
    }
}

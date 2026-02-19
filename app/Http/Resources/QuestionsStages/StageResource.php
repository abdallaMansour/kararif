<?php

namespace App\Http\Resources\QuestionsStages;

use Illuminate\Http\Resources\Json\JsonResource;

class StageResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'number_of_questions' => $this->number_of_questions,
            'back_icon' => $this->getFirstMediaUrl('back_icon'),
            'home_icon' => $this->getFirstMediaUrl('home_icon'),
            'exit_icon' => $this->getFirstMediaUrl('exit_icon'),
            'status' => $this->status,
        ];
    }
}

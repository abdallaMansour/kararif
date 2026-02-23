<?php

namespace App\Http\Resources\Adventurer;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardAdventurerResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'country' => $this->country,
            'email' => $this->email,
            'lifetime_score' => (float) $this->lifetime_score,
            'number_correct_answers' => $this->number_correct_answers,
            'number_wrong_answers' => $this->number_wrong_answers,
            'number_full_winnings' => $this->number_full_winnings,
            'number_surrender_times' => $this->number_surrender_times,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

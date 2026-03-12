<?php

namespace App\Http\Resources\Scoreboard;

use App\Helpers\RankHelper;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreboardEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'lifetime_score' => (float) $this->lifetime_score,
            'wins' => (int) ($this->number_full_winnings ?? 0),
            'rank' => RankHelper::getRankForScore((float) $this->lifetime_score),
            'number_correct_answers' => $this->number_correct_answers,
            'number_wrong_answers' => $this->number_wrong_answers,
            'number_full_winnings' => $this->number_full_winnings,
            'number_surrender_times' => $this->number_surrender_times,
        ];
    }
}

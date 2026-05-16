<?php

namespace App\Http\Resources\Scoreboard;

use App\Helpers\RankHelper;
use App\Services\UserService;
use Illuminate\Http\Resources\Json\JsonResource;

class ScoreboardEntryResource extends JsonResource
{
    public function toArray($request): array
    {
        $stats = app(UserService::class)->getWinsLosses($this->resource);

        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'country' => $this->country,
            'country_code' => $this->country_code,
            'lifetime_score' => (float) $this->lifetime_score,
            'wins' => (int) $stats['wins'],
            'losses' => (int) $stats['losses'],
            'draws' => (int) $stats['draws'],
            'surrenders' => (int) ($this->surrender_count ?? 0),
            'rank' => RankHelper::getRankForScore((float) $this->lifetime_score),
            'number_correct_answers' => $this->number_correct_answers,
            'number_wrong_answers' => $this->number_wrong_answers,
            'number_full_winnings' => (int) $stats['wins'],
            'number_surrender_times' => (int) ($this->surrender_count ?? 0),
        ];
    }
}

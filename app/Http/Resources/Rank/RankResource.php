<?php

namespace App\Http\Resources\Rank;

use Illuminate\Http\Resources\Json\JsonResource;

class RankResource extends JsonResource
{
    public function toArray($request): array
    {
        $next = \App\Models\Rank::where('start_score', '>', $this->start_score)->orderBy('start_score')->first();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'wins_required' => (int) $this->start_score,
            'start_score' => (int) $this->start_score,
            'end_score' => $next ? $next->start_score - 1 : null,
            'icon' => $this->getFirstMediaUrl(),
            'prize_type' => $this->prize_type,
            'prize_value' => $this->prize_value !== null ? (int) $this->prize_value : null,
            'prize_label' => $this->prize_label,
        ];
    }
}

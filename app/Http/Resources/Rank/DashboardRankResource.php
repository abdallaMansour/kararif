<?php

namespace App\Http\Resources\Rank;

use Illuminate\Http\Resources\Json\JsonResource;

class DashboardRankResource extends JsonResource
{
    public function toArray($request): array
    {
        $next = $this->getNextRank();
        return [
            'id' => $this->id,
            'name' => $this->name,
            'start_score' => $this->start_score,
            'end_score' => $next ? $next->start_score - 1 : null,
            'icon' => $this->getFirstMediaUrl(),
            'prize_type' => $this->prize_type,
            'prize_value' => $this->prize_value,
            'prize_label' => $this->prize_label,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function getNextRank()
    {
        return \App\Models\Rank::where('start_score', '>', $this->start_score)->orderBy('start_score')->first();
    }
}

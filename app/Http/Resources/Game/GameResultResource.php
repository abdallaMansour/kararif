<?php

namespace App\Http\Resources\Game;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'scores' => $this->resource['scores'],
            'winnerId' => $this->resource['winnerId'] ?? null,
            'roundsPlayed' => $this->resource['roundsPlayed'],
        ];
    }
}

<?php

namespace App\Http\Resources\Game;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GameSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sessionId' => $this->id,
            'round' => $this->current_round,
            'status' => $this->status,
            'question' => $this->when(isset($this->additional['question']), $this->additional['question'] ?? null),
            'options' => $this->when(isset($this->additional['options']), $this->additional['options'] ?? null),
            'timeLeft' => $this->when(isset($this->additional['timeLeft']), $this->additional['timeLeft'] ?? null),
            'teams' => $this->when(isset($this->additional['teams']), $this->additional['teams'] ?? null),
        ];
    }
}

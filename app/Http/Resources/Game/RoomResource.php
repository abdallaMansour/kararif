<?php

namespace App\Http\Resources\Game;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'roomId' => $this->id,
            'code' => $this->code,
            'status' => $this->status,
            'joinedCount' => $this->room_players_count ?? $this->roomPlayers()->count(),
            'gameTitle' => $this->title ?? $this->type?->name,
            'rounds' => $this->rounds,
            'teams' => $this->teams,
            'players' => $this->players,
            'settings' => [
                'questionType' => $this->type_id,
                'mainCategoryId' => $this->category_id,
                'subCategoryId' => $this->subcategory_id,
                'title' => $this->title,
                'rounds' => $this->rounds,
                'teams' => $this->teams,
                'players' => $this->players,
            ],
            'expiresAt' => $this->expires_at?->toIso8601String(),
        ];
    }
}

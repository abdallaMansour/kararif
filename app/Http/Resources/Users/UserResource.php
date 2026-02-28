<?php

namespace App\Http\Resources\Users;

use App\Helpers\RankHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $avatarRelation = $this->avatarRelation;
        $avatarPayload = $avatarRelation ? [
            'id' => (string) $avatarRelation->id,
            'name' => $avatarRelation->name,
            'image' => $avatarRelation->image_url,
        ] : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullName' => $this->name,
            'username' => $this->username ?? null,
            'email' => $this->email,
            'phone' => $this->phone,
            'avatar' => $avatarPayload,
            'badge' => null,
            'rank' => RankHelper::getRankForScore((float) ($this->points ?? 0)),
            'country' => [
                'label' => $this->country_label,
                'code' => $this->country_code,
            ],
            'surrender_count' => (int) ($this->surrender_count ?? 0),
            'available_sessions' => (int) ($this->available_sessions ?? 0),
            'stats' => [
                'wins' => 0,
                'losses' => 0,
            ],
        ];
    }
}

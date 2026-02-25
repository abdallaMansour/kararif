<?php

namespace App\Http\Resources\Users;

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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'fullName' => $this->name,
            'username' => $this->username ?? null,
            'email' => $this->email,
            'phone' => $this->phone,
            'image' => $this->getFirstMediaUrl() ?? null,
            'avatar' => $this->avatar ?? $this->getFirstMediaUrl() ?? null,
            'badge' => null,
            'stats' => [
                'wins' => 0,
                'losses' => 0,
            ],
        ];
    }
}

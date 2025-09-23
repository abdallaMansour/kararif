<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use App\Http\Resources\Auth\RoleResource;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'name'                 => $this->name,
            'email'                => $this->email,
            'phone'                => $this->phone,
            'type'                 => $this->type,
            'avatar'               => $this->image,
            'device_token'         => $this->device_token,
            'preferred_locale'     => $this->preferred_locale,
            // 'token'                => $this->createTokenForDevice($request->device_name),
            'token'                => $this->createToken('Access Token', expiresAt: now()->addDay())->plainTextToken,
            'reset_token'          => $this->reset_token ?: '',
            'verified'             => $this->hasVerifiedEmail(),
            'verified_at'          => $this->email_verified_at ? $this->email_verified_at->toDateTimeString() : '',
            'created_at'           => $this->created_at->toDateTimeString(),
            'created_at_formatted' => $this->created_at->diffForHumans(),
            'roles'                => RoleResource::Collection($this->roles),
        ];
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;

class AppLoginController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->loginAdventurer($request->only('identifier', 'email', 'password', 'device_name'));

        $expiresIn = 3600;
        $token = $user->createToken('Access Token', expiresAt: now()->addSeconds($expiresIn))->plainTextToken;

        $userData = [
            'id' => (string) $user->id,
            'username' => $user->username ?? '',
            'email' => $user->email,
            'fullName' => $user->name,
            'phone' => $user->phone,
            'avatar' => $user->avatarRelation ? ['id' => (string) $user->avatarRelation->id, 'name' => $user->avatarRelation->name, 'image' => $user->avatarRelation->image_url] : null,
            'badge' => null,
            'country' => [
                'label' => $user->country_label ?? null,
                'code' => $user->country_code ?? null,
            ],
        ];

        return ApiResponse::success([
            'token' => $token,
            'expiresIn' => $expiresIn,
            'user' => $userData,
        ], 'تم تسجيل الدخول بنجاح', 200);
    }
}


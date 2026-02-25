<?php

namespace App\Http\Controllers\Auth;

use App\Helpers\ApiResponse;
use App\Services\AuthService;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;

class LoginController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->loginUser($request->only('identifier', 'email', 'password', 'device_name'));

        $expiresIn = 3600;
        $token = $user->createToken('Access Token', expiresAt: now()->addSeconds($expiresIn))->plainTextToken;

        $userData = [
            'id' => (string) $user->id,
            'username' => $user->username ?? '',
            'email' => $user->email,
            'fullName' => $user->name,
            'phone' => $user->phone,
            'avatar' => $user->avatar ?? $user->getFirstMediaUrl() ?? null,
            'badge' => null,
        ];

        return ApiResponse::success([
            'token' => $token,
            'expiresIn' => $expiresIn,
            'user' => $userData,
        ], 'تم تسجيل الدخول بنجاح', 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(): \Illuminate\Http\JsonResponse
    {
        return $this->authService->logoutUser();
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function check_token()
    {
        return auth()->guard('sanctum')->check() ?
            response()->json(['status' => true]) :
            response()->json(['status' => false],  401);
    }
}

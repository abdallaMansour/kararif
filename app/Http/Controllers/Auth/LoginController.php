<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Services\AuthService;

class LoginController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    // Admin/dashboard login: keep original AuthResource shape
    public function login(LoginRequest $request)
    {
        $user = $this->authService->loginUser($request->only('identifier', 'email', 'password', 'device_name'));

        return new AuthResource($user);
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

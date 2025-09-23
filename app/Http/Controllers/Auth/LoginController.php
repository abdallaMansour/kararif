<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use App\Services\AuthService;
use App\Services\UserService;
use App\Http\Requests\LoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use App\Http\Resources\Auth\AuthResource;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginController extends Controller
{
    protected $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function login(LoginRequest $request)
    {
        $user = $this->authService->loginUser($request->only('email', 'password', 'device_name'));

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

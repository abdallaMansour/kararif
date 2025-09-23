<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\Users\ChangeImageRequest;
use App\Http\Requests\Users\ChangePasswordRequest;
use App\Http\Requests\Users\ChangeUserInfoRequest;
use App\Http\Resources\Users\UserResource;
use App\Services\UserService;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }
    public function changePassword(ChangePasswordRequest $request)
    {
        return $this->userService->changePassword($request->only('current_password', 'password'));
    }
    public function changeImage(ChangeImageRequest $request)
    {
        return $this->userService->changeImage($request->file('image'));
    }
    public function changeInfo(ChangeUserInfoRequest $request)
    {
        return $this->userService->changeInfo($request->validated());
    }

    public function getProfile()
    {
        return new UserResource(auth()->guard('sanctum')->user());
    }
}

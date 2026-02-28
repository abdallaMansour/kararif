<?php

namespace App\Http\Controllers\Avatar;

use App\Models\Avatar;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Avatar\AvatarRequest;
use App\Http\Resources\Avatar\DashboardAvatarResource;
use Illuminate\Http\JsonResponse;

class DashboardAvatarController extends Controller
{
    use ApiTrait;

    public function index(): JsonResponse
    {
        $avatars = Avatar::orderBy('id')->get();
        return $this->sendResponse(DashboardAvatarResource::collection($avatars)->resolve(), null);
    }

    public function show(Avatar $avatar): JsonResponse
    {
        return $this->sendResponse((new DashboardAvatarResource($avatar))->resolve(), null);
    }

    public function store(AvatarRequest $request): JsonResponse
    {
        try {
            $avatar = Avatar::create($request->validated());
            return $this->sendResponse((new DashboardAvatarResource($avatar))->resolve(), __('response.created'), 201);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(AvatarRequest $request, Avatar $avatar): JsonResponse
    {
        try {
            $avatar->update($request->validated());
            return $this->sendResponse((new DashboardAvatarResource($avatar->fresh()))->resolve(), __('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Avatar $avatar): JsonResponse
    {
        try {
            $avatar->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

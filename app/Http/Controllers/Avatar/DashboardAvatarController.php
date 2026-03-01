<?php

namespace App\Http\Controllers\Avatar;

use App\Models\Avatar;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Avatar\AvatarRequest;
use App\Http\Resources\Avatar\DashboardAvatarResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

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
            $data = $this->resolveAvatarImage($request, $request->validated());
            $avatar = Avatar::create($data);
            return $this->sendResponse((new DashboardAvatarResource($avatar))->resolve(), __('response.created'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(AvatarRequest $request, Avatar $avatar): JsonResponse
    {
        try {
            $data = $this->resolveAvatarImage($request, $request->validated());
            if ($request->hasFile('image') && $avatar->image && ! str_starts_with((string) $avatar->image, 'http') && Storage::disk('public')->exists($avatar->image)) {
                Storage::disk('public')->delete($avatar->image);
            }
            $avatar->update($data);
            return $this->sendResponse((new DashboardAvatarResource($avatar->fresh()))->resolve(), __('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Avatar $avatar): JsonResponse
    {
        try {
            if ($avatar->image && ! str_starts_with((string) $avatar->image, 'http') && Storage::disk('public')->exists($avatar->image)) {
                Storage::disk('public')->delete($avatar->image);
            }
            $avatar->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    private function resolveAvatarImage(AvatarRequest $request, array $data): array
    {
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('avatars', 'public');
        } elseif ($request->filled('image')) {
            $data['image'] = $request->input('image');
        }
        return $data;
    }
}

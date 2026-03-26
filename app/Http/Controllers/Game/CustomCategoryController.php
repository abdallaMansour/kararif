<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Game\CustomCategoryRequest;
use App\Models\Adventurer;
use App\Models\CustomCategory;
use Illuminate\Http\JsonResponse;

class CustomCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $user = auth()->user();
        $items = CustomCategory::ownedBy($user)
            ->withCount('questions')
            ->orderByDesc('id')
            ->get()
            ->map(fn (CustomCategory $category) => [
                'id' => (string) $category->id,
                'name' => $category->name,
                'status' => (bool) $category->status,
                'questions_count' => (int) ($category->questions_count ?? 0),
                'created_at' => $category->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return ApiResponse::success($items);
    }

    public function show(int $id): JsonResponse
    {
        $user = auth()->user();
        $category = CustomCategory::ownedBy($user)
            ->withCount('questions')
            ->find($id);

        if (!$category) {
            return ApiResponse::error('Custom category not found.', 404);
        }

        return ApiResponse::success([
            'id' => (string) $category->id,
            'name' => $category->name,
            'status' => (bool) $category->status,
            'questions_count' => (int) ($category->questions_count ?? 0),
            'created_at' => $category->created_at?->toIso8601String(),
        ]);
    }

    public function store(CustomCategoryRequest $request): JsonResponse
    {
        $user = auth()->user();
        $payload = $request->validated();
        $payload['status'] = (bool) ($payload['status'] ?? true);

        if ($user instanceof Adventurer) {
            $payload['owner_adventurer_id'] = $user->id;
        } else {
            $payload['owner_user_id'] = $user->id;
        }

        $category = CustomCategory::create($payload);

        return ApiResponse::success([
            'id' => (string) $category->id,
            'name' => $category->name,
            'status' => (bool) $category->status,
        ], null, 201);
    }

    public function update(CustomCategoryRequest $request, int $id): JsonResponse
    {
        $user = auth()->user();
        $category = CustomCategory::ownedBy($user)->find($id);

        if (!$category) {
            return ApiResponse::error('Custom category not found.', 404);
        }

        $category->update($request->validated());

        return ApiResponse::success([
            'id' => (string) $category->id,
            'name' => $category->name,
            'status' => (bool) $category->status,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();
        $category = CustomCategory::ownedBy($user)->find($id);

        if (!$category) {
            return ApiResponse::error('Custom category not found.', 404);
        }

        $category->delete();

        return ApiResponse::success(['deleted' => true]);
    }
}

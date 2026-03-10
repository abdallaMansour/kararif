<?php

namespace App\Http\Controllers\News;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardNewsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', $request->get('limit', 15));
        $items = News::orderByDesc('published_at')->paginate($perPage);
        $data = $items->getCollection()->map(fn ($n) => [
            'id' => (string) $n->id,
            'title' => $n->title,
            'summary' => $n->summary,
            'body' => $n->body,
            'thumbnail' => $n->thumbnail,
            'url' => $n->url,
            'published_at' => $n->published_at?->toIso8601String(),
        ])->values()->all();

        return ApiResponse::success([
            'data' => $data,
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(News $news): JsonResponse
    {
        return ApiResponse::success([
            'id' => (string) $news->id,
            'title' => $news->title,
            'summary' => $news->summary,
            'body' => $news->body,
            'thumbnail' => $news->thumbnail,
            'url' => $news->url,
            'published_at' => $news->published_at?->toIso8601String(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
        ]);

        $news = News::create($validated);
        return ApiResponse::success(['id' => (string) $news->id], __('response.created'), 201);
    }

    public function update(Request $request, News $news): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'summary' => ['nullable', 'string'],
            'body' => ['nullable', 'string'],
            'thumbnail' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'max:500'],
            'published_at' => ['nullable', 'date'],
        ]);

        $news->update($validated);
        return ApiResponse::success(null, __('response.updated'), 200);
    }

    public function destroy(News $news): JsonResponse
    {
        $news->delete();
        return ApiResponse::success(null, __('response.deleted'), 200);
    }
}

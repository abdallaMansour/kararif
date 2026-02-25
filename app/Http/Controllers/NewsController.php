<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\News;
use Illuminate\Http\JsonResponse;

class NewsController extends Controller
{
    public function index(): JsonResponse
    {
        $limit = (int) request('limit', 10);
        $items = News::orderByDesc('published_at')
            ->limit($limit)
            ->get()
            ->map(fn ($n) => [
                'id' => (string) $n->id,
                'title' => $n->title,
                'summary' => $n->summary,
                'date' => $n->published_at?->toIso8601String(),
                'thumbnail' => $n->thumbnail,
                'url' => $n->url,
            ])
            ->values()
            ->all();

        return ApiResponse::success($items);
    }
}

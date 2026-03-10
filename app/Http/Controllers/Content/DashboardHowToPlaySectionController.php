<?php

namespace App\Http\Controllers\Content;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\HowToPlaySection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardHowToPlaySectionController extends Controller
{
    public function index(): JsonResponse
    {
        $sections = HowToPlaySection::orderBy('order')->orderBy('id')->get()
            ->map(fn ($s) => [
                'id' => (string) $s->id,
                'title' => $s->title,
                'content' => $s->content,
                'order' => $s->order,
            ])->values()->all();

        return ApiResponse::success(['data' => $sections]);
    }

    public function show(HowToPlaySection $howToPlaySection): JsonResponse
    {
        return ApiResponse::success([
            'id' => (string) $howToPlaySection->id,
            'title' => $howToPlaySection->title,
            'content' => $howToPlaySection->content,
            'order' => $howToPlaySection->order,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['order'] = $validated['order'] ?? HowToPlaySection::max('order') + 1;
        $section = HowToPlaySection::create($validated);
        return ApiResponse::success(['id' => (string) $section->id], __('response.created'), 201);
    }

    public function update(Request $request, HowToPlaySection $howToPlaySection): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ]);

        $howToPlaySection->update($validated);
        return ApiResponse::success(null, __('response.updated'), 200);
    }

    public function destroy(HowToPlaySection $howToPlaySection): JsonResponse
    {
        $howToPlaySection->delete();
        return ApiResponse::success(null, __('response.deleted'), 200);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\HowToPlaySection;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function howToPlay(): JsonResponse
    {
        $sections = HowToPlaySection::orderBy('order')->orderBy('id')->get()
            ->map(fn ($s) => ['title' => $s->title, 'content' => $s->content])
            ->values()
            ->all();

        return ApiResponse::success(['sections' => $sections]);
    }
}

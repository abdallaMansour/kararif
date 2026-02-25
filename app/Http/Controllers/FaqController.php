<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Models\FaqItem;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FaqItem::orderBy('order')->orderBy('id')->get()->map(fn ($f) => [
            'id' => (string) $f->id,
            'question' => $f->question,
            'answer' => $f->answer,
        ])->values()->all();

        return ApiResponse::success($items);
    }
}

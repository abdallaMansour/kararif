<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\CustomStage;
use Illuminate\Http\JsonResponse;

class CustomStageController extends Controller
{
    /**
     * Picker list for custom room creation: id, name, cover image only.
     */
    public function index(): JsonResponse
    {
        $items = CustomStage::query()
            ->where('status', true)
            ->orderBy('id')
            ->get()
            ->map(fn (CustomStage $s) => [
                'id' => (string) $s->id,
                'name' => $s->name,
                'cover_image_url' => $s->getFirstMediaUrl('cover_image') ?: null,
            ])
            ->values()
            ->all();

        return ApiResponse::success($items);
    }
}

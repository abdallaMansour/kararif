<?php

namespace App\Http\Controllers\Avatar;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Avatar;
use Illuminate\Http\JsonResponse;

class AvatarController extends Controller
{
    public function index(): JsonResponse
    {
        $avatars = Avatar::orderBy('id')->get()->map(fn (Avatar $a) => [
            'id' => (string) $a->id,
            'name' => $a->name,
            'image' => $a->image,
        ])->values()->all();

        return ApiResponse::success($avatars);
    }
}

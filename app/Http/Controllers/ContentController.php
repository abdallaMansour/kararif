<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class ContentController extends Controller
{
    public function howToPlay(): JsonResponse
    {
        $sections = [
            ['title' => 'كيف تلعب', 'content' => 'اختر نوع الأسئلة والفئة ثم ادخل الرمز أو أنشئ غرفة.'],
        ];

        return ApiResponse::success(['sections' => $sections]);
    }
}

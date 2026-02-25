<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\SubscribeRequest;
use App\Models\Subscriber;
use Illuminate\Http\JsonResponse;

class SubscriberController extends Controller
{
    public function store(SubscribeRequest $request): JsonResponse
    {
        $data = $request->only('email', 'full_name', 'country_code');
        $data['full_name'] = $data['full_name'] ?? $request->input('fullName');

        Subscriber::firstOrCreate(
            ['email' => $data['email']],
            $data
        );

        return ApiResponse::success(null, 'تم تسجيل اشتراكك بنجاح', 200);
    }
}

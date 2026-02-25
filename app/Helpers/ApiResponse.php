<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    public static function success($data = null, string $message = null, int $code = 200): JsonResponse
    {
        $payload = ['success' => true];
        if ($message !== null) {
            $payload['message'] = $message;
        }
        if ($data !== null) {
            $payload['data'] = $data;
        }
        return response()->json($payload, $code);
    }

    public static function error(string $message, int $code = 400, array $errors = null): JsonResponse
    {
        $payload = [
            'success' => false,
            'message' => $message,
        ];
        if ($errors !== null) {
            $payload['errors'] = $errors;
        }
        return response()->json($payload, $code);
    }
}

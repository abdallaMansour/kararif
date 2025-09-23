<?php

namespace App\Helpers;

class ApiResponse
{
    static function success($message = '', $code = 200)
    {
        if ($message != '')
            return response()->json(['message' => $message], $code);

        // Like 201 created successfully
        return response()->json(status: $code);
    }

    static function error($message, $code = 400)
    {
        return response()->json([
            'message' => $message
        ], $code);
    }

    static function response($data, $code = 200)
    {
        return response()->json(['data' => $data], $code);
    }
}

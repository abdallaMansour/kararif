<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function registerUser(array $data)
    {
        $user = User::create($data);

        $token = $user->createToken('Access Token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function loginUser(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new HttpResponseException(response()->json([
                'message' => 'The provided credentials are incorrect.',
            ], 401));
        }

        // Revoke all tokens...
        $user->tokens()->delete();

        // Revoke a specific token...
        $user->tokens()->where('id', $user->id)->delete();

        return $user;
    }

    /** @return \Illuminate\Http\JsonResponse */
    public function logoutUser(): \Illuminate\Http\JsonResponse
    {
        try {
            auth()->guard('sanctum')->user()->tokens()->delete();

            return response()->json([
                'message' => 'logged out'
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'message' => 'Error on logout : ' . $th->getMessage()
            ], 500);
        }
    }
}

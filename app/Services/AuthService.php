<?php

namespace App\Services;

use App\Models\Avatar;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function registerUser(array $data)
    {
        $data['password'] = Hash::make($data['password']);

        if (empty($data['avatar_id'])) {
            $data['avatar_id'] = Avatar::inRandomOrder()->value('id');
        }

        $user = User::create($data);
        return $user;
    }

    public function loginUser(array $data)
    {
        $identifier = $data['identifier'] ?? $data['email'] ?? null;
        if (!$identifier) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401));
        }

        $user = User::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
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
                'success' => true,
                'message' => 'تم تسجيل الخروج',
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Error on logout : ' . $th->getMessage()
            ], 500);
        }
    }
}

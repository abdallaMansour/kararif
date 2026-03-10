<?php

namespace App\Services;

use App\Models\Adventurer;
use App\Models\Avatar;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function loginUser(array $data): User
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

        if (!$user || !Hash::check($data['password'] ?? '', $user->password)) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401));
        }

        return $user;
    }

    public function registerAdventurer(array $data): Adventurer
    {
        $data['password'] = Hash::make($data['password']);

        if (empty($data['avatar_id'])) {
            $data['avatar_id'] = Avatar::inRandomOrder()->value('id');
        }

        return Adventurer::create(array_merge($data, [
            'available_sessions' => 2,
        ]));
    }

    public function loginAdventurer(array $data): Adventurer
    {
        $identifier = $data['identifier'] ?? $data['email'] ?? null;
        if (!$identifier) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401));
        }

        $adventurer = Adventurer::where('email', $identifier)
            ->orWhere('username', $identifier)
            ->first();

        $password = $data['password'] ?? '';
        $checkPassword = $adventurer?->password ? Hash::check($password, $adventurer->password) : false;
        if (!$adventurer || !$checkPassword) {
            throw new HttpResponseException(response()->json([
                'success' => false,
                'message' => 'اسم المستخدم أو كلمة المرور غير صحيحة',
            ], 401));
        }

        return $adventurer;
    }

    public function logoutUser(): \Illuminate\Http\JsonResponse
    {
        try {
            $user = auth()->user();
            if ($user) {
                $user->tokens()->delete();
            }
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

<?php

namespace App\Services;

use App\Models\User;
use App\Utils\ImageUpload;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function changePassword(array $data)
    {
        $user = auth()->guard('sanctum')->user();

        if (!Hash::check($data['current_password'], $user->password)) {
            return response()->json(['error' => 'The current password is incorrect.'], 401);
        }
        $user->password = Hash::make($data['password']);
        $user->save();

        return response()->json(['message' => 'password updated successfully']);
    }

    public function changeImage($image)
    {
        try {
            /** @var User $user */
            $user = Auth::user();

            $user->clearMediaCollection();
            $user->addMedia($image)->toMediaCollection();

            $user->save();

            return response()->json(['message' => 'user image updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }


    public function changeInfo(array $data)
    {
        try {
            /** @var User $user */
            $user = auth()->guard('sanctum')->user();
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
            ]);
            return response()->json(['message' => 'user updated successfully']);
        } catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()], 500);
        }
    }
    public function createAdmin($data)
    {
        $user = User::create($data->allWithHashedPassword());
        $user->type = User::ADMIN_TYPE;
        $user->save();

        if ($data->roles) {
            foreach ($data->roles as $roleId) {
                $user->roles()->attach($roleId, ['user_type' => User::class]);
            }
        }

        if ($data->hasFile('image')) {
            $imagePath = ImageUpload::uploadImage($data->file('image'), 'images/users');
            $user->image = $imagePath;
            $user->save();
        }

        $user->refresh();
        return $user;
    }
}

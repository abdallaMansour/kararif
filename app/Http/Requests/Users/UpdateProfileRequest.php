<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'fullName' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20|unique:users,phone,' . auth()->guard('sanctum')->id(),
            'avatar' => 'sometimes|nullable|string|max:500',
            'newPassword' => 'sometimes|string|min:6|confirmed',
        ];

        return $rules;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first() ?: 'كلمتا المرور غير متطابقتين',
            'errors' => $validator->errors(),
        ], 400));
    }
}

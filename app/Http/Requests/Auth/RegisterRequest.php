<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fullName' => 'required|string|max:255',
            'username' => 'nullable|string|max:255|unique:adventurers,username',
            'email' => 'required|email|unique:adventurers,email',
            'phone' => 'nullable|string|max:20|unique:adventurers,phone',
            'password' => 'required|string|size:4|confirmed',
            'countryLabel' => 'nullable|string|max:255',
            'countryCode' => 'nullable|string|max:10',
            'avatarId' => 'nullable|integer|exists:avatars,id',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first() ?: 'رسالة الخطأ',
            'errors' => $validator->errors(),
        ], 422));
    }

    public function validated($key = null, $default = null): array
    {
        $data = parent::validated($key, $default);
        $data['name'] = $data['fullName'] ?? '';
        $data['country_label'] = $data['countryLabel'] ?? null;
        $data['country_code'] = $data['countryCode'] ?? null;
        $data['avatar_id'] = $data['avatarId'] ?? null;
        unset($data['fullName'], $data['countryLabel'], $data['countryCode'], $data['avatarId']);
        if (empty($data['phone'])) {
            $data['phone'] = null;
        }
        return $data;
    }
}

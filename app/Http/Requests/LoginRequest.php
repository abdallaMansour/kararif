<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => 'nullable|string',
            'email' => 'required_without:identifier|nullable|email',
            // Allow both legacy longer passwords and new 4-character ones
            'password' => 'required|string|min:4',
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
}

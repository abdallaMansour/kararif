<?php

namespace App\Http\Requests\Avatar;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AvatarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageRule = $this->hasFile('image')
            ? ['nullable', 'image', 'mimes:jpeg,png,gif,webp', 'max:2048']
            : ['nullable', 'string', 'max:2048'];

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'image' => $imageRule,
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}

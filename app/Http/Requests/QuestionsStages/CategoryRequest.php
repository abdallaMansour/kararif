<?php

namespace App\Http\Requests\QuestionsStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CategoryRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'type_id' => ['required', 'exists:types,id'],
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'error' => $validator->errors()->first()
        ], 400));
    }
}

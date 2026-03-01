<?php

namespace App\Http\Requests\Rank;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class RankRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'start_score' => ['required', 'integer', 'min:0'],
            'icon' => ['nullable', 'image'],
            'prize_type' => ['nullable', 'string', 'in:discount_next_5_purchases,free_sessions'],
            'prize_value' => ['nullable', 'integer', 'min:0'],
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

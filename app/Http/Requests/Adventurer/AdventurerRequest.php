<?php

namespace App\Http\Requests\Adventurer;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdventurerRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'pin_code' => ['nullable', 'string', 'size:4'],
            'lifetime_score' => ['nullable', 'numeric', 'min:0'],
            'number_correct_answers' => ['nullable', 'integer', 'min:0'],
            'number_wrong_answers' => ['nullable', 'integer', 'min:0'],
            'number_full_winnings' => ['nullable', 'integer', 'min:0'],
            'number_surrender_times' => ['nullable', 'integer', 'min:0'],
        ];
        $adventurer = $this->route('adventurer');
        if (!$adventurer) {
            $rules['email'][] = 'unique:adventurers,email';
            $rules['pin_code'] = ['required', 'string', 'size:4'];
        } else {
            $rules['email'][] = 'unique:adventurers,email,' . $adventurer->id;
        }
        return $rules;
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

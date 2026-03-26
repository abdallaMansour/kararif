<?php

namespace App\Http\Requests\Game;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'custom_category_id' => ['nullable', Rule::exists('custom_categories', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'answer_1' => ['required', 'string'],
            'is_correct_1' => ['nullable', 'boolean'],
            'answer_2' => ['required', 'string'],
            'is_correct_2' => ['nullable', 'boolean'],
            'answer_3' => ['required', 'string'],
            'is_correct_3' => ['nullable', 'boolean'],
            'answer_4' => ['required', 'string'],
            'is_correct_4' => ['nullable', 'boolean'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $correctCount = array_sum([
                (bool) $this->input('is_correct_1'),
                (bool) $this->input('is_correct_2'),
                (bool) $this->input('is_correct_3'),
                (bool) $this->input('is_correct_4'),
            ]);

            if ($correctCount !== 1) {
                $validator->errors()->add('is_correct', 'Exactly one answer must be marked as correct.');
            }
        });
    }
}

<?php

namespace App\Http\Requests\QuestionsStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuestionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'stage_id' => ['required', 'exists:stages,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'type_id' => ['required', 'exists:types,id'],
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
            $correct = [
                (bool) $this->input('is_correct_1'),
                (bool) $this->input('is_correct_2'),
                (bool) $this->input('is_correct_3'),
                (bool) $this->input('is_correct_4'),
            ];
            if (array_sum($correct) !== 1) {
                $validator->errors()->add('is_correct', __('Exactly one answer must be marked as correct.'));
            }
        });
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

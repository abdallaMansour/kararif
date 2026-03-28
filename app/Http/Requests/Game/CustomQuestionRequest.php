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

    protected function prepareForValidation(): void
    {
        if ($this->has('customCategoryId') && ! $this->has('custom_category_id')) {
            $this->merge([
                'custom_category_id' => $this->input('customCategoryId'),
            ]);
        }
    }

    public function rules(): array
    {
        $categoryExists = Rule::exists('custom_categories', 'id');

        if ($this->isMethod('PATCH')) {
            return [
                'custom_category_id' => ['sometimes', 'required', 'integer', $categoryExists],
                'name' => ['sometimes', 'required', 'string', 'max:255'],
                'answer_1' => ['sometimes', 'string'],
                'is_correct_1' => ['nullable', 'boolean'],
                'answer_2' => ['sometimes', 'string'],
                'is_correct_2' => ['nullable', 'boolean'],
                'answer_3' => ['sometimes', 'string'],
                'is_correct_3' => ['nullable', 'boolean'],
                'answer_4' => ['sometimes', 'string'],
                'is_correct_4' => ['nullable', 'boolean'],
                'status' => ['nullable', 'boolean'],
            ];
        }

        return [
            'custom_category_id' => ['required', 'integer', $categoryExists],
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
            $answerKeys = [
                'answer_1', 'answer_2', 'answer_3', 'answer_4',
                'is_correct_1', 'is_correct_2', 'is_correct_3', 'is_correct_4',
            ];
            $answerTouched = collect($answerKeys)->contains(fn (string $k) => $this->has($k));

            if ($this->isMethod('PATCH') && $answerTouched) {
                foreach (['answer_1', 'answer_2', 'answer_3', 'answer_4'] as $k) {
                    if (! $this->filled($k)) {
                        $validator->errors()->add($k, 'All four answers are required when updating answers.');
                    }
                }
            }

            if ($this->isMethod('PATCH') && ! $answerTouched) {
                return;
            }

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

<?php

namespace App\Http\Requests\QuestionsStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StageRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'stage_type' => ['required', 'in:questions_group,life_points'],
            'question_groups_count' => ['nullable', 'integer', 'min:1'],
            'number_of_questions' => ['nullable', 'integer', 'min:0'],
            'life_points_per_question' => ['nullable', 'numeric', 'min:0'],
            'back_icon' => ['nullable', 'file'],
            'home_icon' => ['nullable', 'file'],
            'exit_icon' => ['nullable', 'file'],
            'start_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'end_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'correct_answer_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'wrong_answer_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'status' => ['nullable', 'boolean'],
        ];
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->stage_type === 'questions_group') {
                if (!$this->filled('question_groups_count') || (int) $this->question_groups_count < 1) {
                    $validator->errors()->add('question_groups_count', __('Question groups count is required for Questions Group Stage.'));
                }
            }
            if ($this->stage_type === 'life_points') {
                if (!$this->filled('number_of_questions')) {
                    $validator->errors()->add('number_of_questions', __('Number of questions is required for Life Points Stage.'));
                }
                if (!$this->filled('life_points_per_question') && $this->life_points_per_question !== '0') {
                    $validator->errors()->add('life_points_per_question', __('Life points per question is required for Life Points Stage.'));
                }
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

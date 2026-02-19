<?php

namespace App\Http\Requests\QuestionsStages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuestionVideosRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'start_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'lunch_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'question_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'correct_answer_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'wrong_answer_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
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

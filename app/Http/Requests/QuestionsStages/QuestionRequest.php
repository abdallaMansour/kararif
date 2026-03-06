<?php

namespace App\Http\Requests\QuestionsStages;

use App\Models\Question;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class QuestionRequest extends FormRequest
{
    public function rules(): array
    {
        $rules = [
            'type_id' => ['required', 'exists:types,id'],
            'category_id' => ['required', 'exists:categories,id'],
            'subcategory_id' => ['required', 'exists:subcategories,id'],
            'name' => ['required', 'string', 'max:255'],
            'question_kind' => ['required', 'in:normal,words,voice,video,image'],
            'status' => ['nullable', 'boolean'],
        ];

        $kind = $this->input('question_kind', 'normal');

        // All kinds (including words) use 4 options with one correct answer
        $rules['answer_1'] = ['required', 'string'];
        $rules['is_correct_1'] = ['nullable', 'boolean'];
        $rules['answer_2'] = ['required', 'string'];
        $rules['is_correct_2'] = ['nullable', 'boolean'];
        $rules['answer_3'] = ['required', 'string'];
        $rules['is_correct_3'] = ['nullable', 'boolean'];
        $rules['answer_4'] = ['required', 'string'];
        $rules['is_correct_4'] = ['nullable', 'boolean'];

        // For words kind, require the raw word string (letters separated by spaces)
        if ($kind === Question::KIND_WORDS) {
            $rules['word'] = ['required', 'string'];
        }

        if ($kind === Question::KIND_IMAGE) {
            $rules['image'] = ['nullable', 'image', 'max:2048'];
        }
        if ($kind === Question::KIND_VOICE) {
            $rules['voice'] = ['nullable', 'file', 'mimetypes:audio/mpeg,audio/mp3,audio/wav,audio/ogg,audio/webm'];
        }
        if ($kind === Question::KIND_VIDEO) {
            $rules['video'] = ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:30720'];
        }

        return $rules;
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $kind = $this->input('question_kind', 'normal');
            if ($kind === Question::KIND_WORDS || in_array($kind, [Question::KIND_NORMAL, Question::KIND_VOICE, Question::KIND_VIDEO, Question::KIND_IMAGE])) {
                $raw = trim((string) $this->input('word', ''));
                $letters = $raw === '' ? [] : preg_split('/\s+/', $raw);
                if (empty($letters)) {
                    if ($kind === Question::KIND_WORDS) {
                        $validator->errors()->add('word', __('Word letters must contain at least one letter.'));
                    }
                }
            }
            if (in_array($kind, [Question::KIND_NORMAL, Question::KIND_WORDS, Question::KIND_VOICE, Question::KIND_VIDEO, Question::KIND_IMAGE])) {
                $correct = [
                    (bool) $this->input('is_correct_1'),
                    (bool) $this->input('is_correct_2'),
                    (bool) $this->input('is_correct_3'),
                    (bool) $this->input('is_correct_4'),
                ];
                if (array_sum($correct) !== 1) {
                    $validator->errors()->add('is_correct', __('Exactly one answer must be marked as correct.'));
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

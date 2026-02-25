<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class SubmitAnswerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answerId' => 'sometimes|integer',
            'optionIndex' => 'sometimes|integer|min:1|max:4',
            'teamId' => 'nullable|integer',
        ];
    }
}

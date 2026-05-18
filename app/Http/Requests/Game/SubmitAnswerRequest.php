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
            'answerId' => 'sometimes',
            'optionIndex' => 'sometimes',
            'teamId' => 'nullable|integer',
        ];
    }
}

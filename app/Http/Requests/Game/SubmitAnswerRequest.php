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
            'optionIndex' => 'sometimes|integer|min:1|max:4',
            'shape' => 'sometimes|string|in:triangle,circle,x,square',
            'teamId' => 'nullable|integer',
        ];
    }
}

<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class CreateCustomRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customCategoryId' => 'required|exists:custom_categories,id',
            'customStageId' => 'required|integer|exists:custom_stages,id',
            'title' => 'nullable|string|max:255',
            'rounds' => 'nullable|integer|min:1|max:20',
            'questionsCount' => 'nullable|integer|min:1|max:50',
            'teams' => 'nullable|integer|min:1|max:4',
            'players' => 'nullable|integer|min:1|max:10',
            'life_points' => 'nullable|integer|min:1|max:20',
        ];
    }
}

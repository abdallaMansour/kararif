<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questionType' => 'required|exists:types,id',
            'mainCategoryId' => 'required|exists:categories,id',
            'subCategoryId' => 'required|exists:subcategories,id',
            'title' => 'nullable|string|max:255',
            'rounds' => 'nullable|integer|min:1|max:20',
            'questionsCount' => 'nullable|integer|min:1|max:50',
            'teams' => 'nullable|integer|min:1|max:4',
            'players' => 'nullable|integer|min:1|max:10',
        ];
    }
}

<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class FaqItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'question' => ['required', 'string', 'max:1000'],
            'answer' => ['required', 'string'],
            'order' => ['nullable', 'integer', 'min:0'],
        ];
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['question'] = ['sometimes', 'required', 'string', 'max:1000'];
            $rules['answer'] = ['sometimes', 'required', 'string'];
        }
        return $rules;
    }
}

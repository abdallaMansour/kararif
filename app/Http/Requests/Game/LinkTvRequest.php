<?php

namespace App\Http\Requests\Game;

use Illuminate\Foundation\Http\FormRequest;

class LinkTvRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tvCode' => ['required', 'string', 'size:6'],
        ];
    }
}

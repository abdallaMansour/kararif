<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubscribeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'fullName' => 'nullable|string|max:255',
            'countryCode' => 'nullable|string|max:10',
        ];
    }
}

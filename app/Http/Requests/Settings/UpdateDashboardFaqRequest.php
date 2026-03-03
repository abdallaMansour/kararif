<?php

namespace App\Http\Requests\Settings;

use App\Helpers\Languages;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validation = [
            'faqs_image' => 'nullable|image|max:2048',
        ];
        foreach (Languages::LANGS as $lang) {
            $validation['faqs_title_' . $lang] = 'nullable|string|max:255';
            $validation['faqs_content_' . $lang] = 'nullable|string';
        }
        return $validation;
    }
}

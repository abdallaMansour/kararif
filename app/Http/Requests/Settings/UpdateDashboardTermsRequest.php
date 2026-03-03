<?php

namespace App\Http\Requests\Settings;

use App\Helpers\Languages;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardTermsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validation = [
            'terms_conditions_image' => 'nullable|image|max:2048',
        ];
        foreach (Languages::LANGS as $lang) {
            $validation['terms_conditions_last_updated_' . $lang] = 'nullable|string|date_format:Y-m-d';
            $validation['terms_conditions_steps_' . $lang] = 'nullable|array';
            $validation['terms_conditions_steps_' . $lang . '.*.title'] = 'nullable|string|max:500';
            $validation['terms_conditions_steps_' . $lang . '.*.content'] = 'nullable|string';
            $validation['terms_conditions_title_' . $lang] = 'nullable|string|max:255';
            $validation['terms_conditions_content_' . $lang] = 'nullable|string';
        }
        return $validation;
    }
}

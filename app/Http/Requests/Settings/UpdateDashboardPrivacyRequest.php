<?php

namespace App\Http\Requests\Settings;

use App\Helpers\Languages;
use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardPrivacyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $validation = [
            'privacy_policy_image' => 'nullable|image|max:2048',
        ];
        foreach (Languages::LANGS as $lang) {
            $validation['privacy_policy_last_updated_' . $lang] = 'nullable|string|date_format:Y-m-d';
            $validation['privacy_policy_steps_' . $lang] = 'nullable|array';
            $validation['privacy_policy_steps_' . $lang . '.*.title'] = 'nullable|string|max:500';
            $validation['privacy_policy_steps_' . $lang . '.*.content'] = 'nullable|string';
            $validation['privacy_policy_title_' . $lang] = 'nullable|string|max:255';
            $validation['privacy_policy_content_' . $lang] = 'nullable|string';
        }
        return $validation;
    }
}

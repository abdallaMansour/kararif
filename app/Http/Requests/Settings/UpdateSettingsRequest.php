<?php

namespace App\Http\Requests\Settings;

use App\Helpers\Languages;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $validation = [
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'facebook' => 'nullable|url|max:255',
            'instagram' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'x' => 'nullable|url|max:20',
            'whatsapp' => 'nullable|string|max:20',
            'logo' => 'nullable|image|max:2048',
            'faqs_image' => 'nullable|image|max:2048',
            'privacy_policy_image' => 'nullable|image|max:2048',
            'terms_conditions_image' => 'nullable|image|max:2048',
        ];

        foreach (Languages::LANGS as $lang) {
            $validation['address_' . $lang] = 'nullable|string';
            $validation['footer_description_' . $lang] = 'nullable|string';
            $validation['faqs_title_' . $lang] = 'nullable|string|max:255';
            $validation['faqs_content_' . $lang] = 'nullable|string';
            $validation['privacy_policy_title_' . $lang] = 'nullable|string|max:255';
            $validation['privacy_policy_content_' . $lang] = 'nullable|string';
            $validation['terms_conditions_title_' . $lang] = 'nullable|string|max:255';
            $validation['terms_conditions_content_' . $lang] = 'nullable|string';
        }

        return $validation;
    }
}

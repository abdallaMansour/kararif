<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDashboardFaqRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'faqs_image' => 'nullable|image|max:2048',
            'faqs_title_ar' => 'nullable|string|max:255',
            'faqs_content_ar' => 'nullable|string',
        ];
    }
}

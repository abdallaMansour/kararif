<?php

namespace App\Http\Requests\Role;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
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
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($this->roleId ?? null),
            ],
            'permissions' => 'required|array',
            'permissions.*' => 'required|string|exists:permissions,id',
            'display_name_ar' => ['required', 'string', Rule::unique('role_translations', 'display_name')->ignore($this->roleId, 'role_id' ?? null),],
            'display_name_en' => ['required', 'string', Rule::unique('role_translations', 'display_name')->ignore($this->roleId, 'role_id' ?? null),],
        ];
    }
}

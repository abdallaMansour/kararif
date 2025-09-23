<?php

namespace App\Http\Requests\Admin;

use App\Helpers\PageHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Astrotomic\Translatable\Validation\RuleFactory;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateAdminRequest extends FormRequest
{
    use PageHelper;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name'     => ['required', 'string'],
            'email'    => ['required', 'email', 'unique:users,email,' . $this->admin_id],
            'phone'    => ['required', 'string', 'unique:users,phone,' . $this->admin_id],
            'password' => ['nullable', 'string'],
            'roles'    => ['required', 'array'],
            'roles.*'  => ['exists:roles,id'],
            'image'    => ['nullable', 'image'],
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'status' => false,
            'error' => $validator->errors()->first()
        ], 400));
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return RuleFactory::make(trans('validation.attributes'));
    }
}

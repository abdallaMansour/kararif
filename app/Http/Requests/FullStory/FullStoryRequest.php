<?php

namespace App\Http\Requests\FullStory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Astrotomic\Translatable\Validation\RuleFactory;
use Illuminate\Http\Exceptions\HttpResponseException;

class FullStoryRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'integer', 'in:1,2,3'],
            'link' => ['nullable', 'required_if:type,3', 'string', 'url'],
            'image' => ['nullable', 'image'],
            'audios' => ['nullable', 'required_if:type,1', 'array', 'max:5'],
            'audios.*' => ['file'],
            'videos' => ['nullable', 'required_if:type,2', 'array', 'max:10'],
            'videos.*' => ['file'],
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

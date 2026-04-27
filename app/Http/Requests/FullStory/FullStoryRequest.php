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
        $isUpdate = $this->route('full_story') !== null;

        $audioRules = ['nullable', 'array', 'max:5'];
        $videoRules = ['nullable', 'array', 'max:10'];

        if (! $isUpdate) {
            $audioRules[] = 'required_if:type,1';
            $videoRules[] = 'required_if:type,2';
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'is_free' => ['required', 'boolean'],
            'type' => ['required', 'integer', 'in:1,2,3'],
            'link' => ['nullable', 'string', 'url'],
            'image' => ['nullable', 'image'],
            'audios' => $audioRules,
            'audios.*' => ['file'],
            'videos' => $videoRules,
            // NOTE: max is in KB -> 51200 KB = 50 MB
            'videos.*' => ['file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'],
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

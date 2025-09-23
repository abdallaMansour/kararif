<?php

namespace App\Http\Requests\Seo;

use App\Helpers\PageHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Astrotomic\Translatable\Validation\RuleFactory;
use Illuminate\Http\Exceptions\HttpResponseException;

class SeoRequest extends FormRequest
{
    use PageHelper;
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $this->checkFound($this->page);

        if ($this->page == 'home') {
            return $this->homeUpdateRules();
        }

        return $this->updateRules();
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

    /**
     * Get the update validation rules that apply to the request.
     *
     * @return array
     */
    public function homeUpdateRules()
    {
        return [
            'title_ar'       => ['required', 'string'],
            'description_ar' => ['required', 'string'],
            'site_name_ar'   => ['required', 'string'],
            'keyword_ar'     => ['required', 'string'],

            'title_en'       => ['required', 'string'],
            'description_en' => ['required', 'string'],
            'site_name_en'   => ['required', 'string'],
            'keyword_en'     => ['required', 'string'],

            'image'         => ['nullable', 'image'],
            'icon'          => ['nullable', 'image'],
        ];
    }

    /**
     * Get the update validation rules that apply to the request.
     *
     * @return array
     */
    public function updateRules()
    {
        return [
            'title_ar'       => ['required', 'string'],
            'description_ar' => ['required', 'string'],
            'keyword_ar'     => ['required', 'string'],

            'title_en'       => ['required', 'string'],
            'description_en' => ['required', 'string'],
            'keyword_en'     => ['required', 'string'],

            'image'         => ['nullable', 'image'],
            'icon'          => ['nullable', 'image'],
        ];
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

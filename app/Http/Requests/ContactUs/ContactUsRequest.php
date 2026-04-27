<?php

namespace App\Http\Requests\ContactUs;

use App\Helpers\PageHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Astrotomic\Translatable\Validation\RuleFactory;
use Illuminate\Http\Exceptions\HttpResponseException;

class ContactUsRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'category' => ['nullable', 'string', 'max:100'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
            'source' => ['nullable', 'string', 'in:mobile,tv,other'],
            'captcha_token' => ['required', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('captcha_token')) {
                return;
            }

            if (! $this->verifyTurnstileToken((string) $this->input('captcha_token'))) {
                $validator->errors()->add('captcha_token', 'CAPTCHA verification failed. Please try again.');
            }
        });
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
            'success' => false,
            'message' => $validator->errors()->first() ?: 'رسالة الخطأ',
            'errors' => $validator->errors(),
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

    protected function verifyTurnstileToken(string $token): bool
    {
        $secret = (string) config('services.turnstile.secret_key');
        if ($secret === '') {
            return false;
        }

        $verifyUrl = (string) config('services.turnstile.verify_url', 'https://challenges.cloudflare.com/turnstile/v0/siteverify');

        try {
            $response = Http::asForm()->timeout(10)->post($verifyUrl, [
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $this->ip(),
            ]);
        } catch (\Throwable $th) {
            return false;
        }

        if (! $response->ok()) {
            return false;
        }

        return (bool) $response->json('success', false);
    }
}

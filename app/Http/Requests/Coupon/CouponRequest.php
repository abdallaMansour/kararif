<?php

namespace App\Http\Requests\Coupon;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coupon = $this->route('coupon');
        $codeRule = ['required', 'string', 'max:100', 'unique:coupons,code'];
        if ($coupon) {
            $codeRule[3] = 'unique:coupons,code,' . $coupon->id;
        }

        return [
            'title' => ['required', 'string', 'max:255'],
            'code' => $codeRule,
            'usage_per_user' => ['required', 'integer', 'min:1'],
            'discount_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:active,inactive'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422));
    }
}

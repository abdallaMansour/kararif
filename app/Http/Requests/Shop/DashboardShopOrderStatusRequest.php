<?php

namespace App\Http\Requests\Shop;

use App\Models\ShopOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardShopOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in([
                    ShopOrder::STATUS_PENDING_PAYMENT,
                    ShopOrder::STATUS_PAID,
                    ShopOrder::STATUS_FAILED,
                    ShopOrder::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}

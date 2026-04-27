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
                    ShopOrder::STATUS_CONFIRMED,
                    ShopOrder::STATUS_ON_DELIVERY,
                    ShopOrder::STATUS_DELIVERED,
                ]),
            ],
        ];
    }
}

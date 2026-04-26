<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DashboardShopProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            'sku' => ['required', 'string', 'max:120', Rule::unique('shop_products', 'sku')->ignore($productId)],
            'name_ar' => ['required', 'string', 'max:255'],
            'price_aed' => ['required', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'is_active' => ['sometimes', 'boolean'],
            'is_sellable' => ['sometimes', 'boolean'],
        ];
    }
}

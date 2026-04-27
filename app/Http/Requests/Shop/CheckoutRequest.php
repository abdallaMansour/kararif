<?php

namespace App\Http\Requests\Shop;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer.full_name' => ['required', 'string', 'max:255'],
            'customer.phone' => ['required', 'string', 'max:50'],
            'customer.email' => ['required', 'email', 'max:255'],
            'delivery.emirate' => ['required', 'string', 'max:120'],
            'delivery.area' => ['required', 'string', 'max:120'],
            'delivery.detail' => ['required', 'string', 'max:1000'],
            'success_url' => ['nullable', 'url', 'max:1000'],
            'cancel_url' => ['nullable', 'url', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:shop_products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.signature_names' => ['nullable', 'array'],
            'items.*.signature_names.*' => ['nullable', 'string', 'max:120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $clean = fn (?string $value): ?string => $value === null ? null : trim(strip_tags($value));

        $customer = (array) $this->input('customer', []);
        // Accept both "delivery" and "address" payload keys.
        $deliveryInput = $this->input('delivery', $this->input('address', []));
        $delivery = (array) $deliveryInput;
        $items = collect((array) $this->input('items', []))->map(function ($item) use ($clean) {
            $item = (array) $item;
            $signatureNames = collect((array) ($item['signature_names'] ?? []))
                ->map(fn ($name) => $clean(is_string($name) ? $name : null))
                ->filter(fn ($name) => $name !== null && $name !== '')
                ->values()
                ->all();

            return [
                'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
                'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
                'signature_names' => $signatureNames,
            ];
        })->values()->all();

        $this->merge([
            'customer' => [
                'full_name' => $clean($customer['full_name'] ?? null),
                'phone' => $clean($customer['phone'] ?? null),
                'email' => $clean($customer['email'] ?? null),
            ],
            'delivery' => [
                'emirate' => $clean($delivery['emirate'] ?? null),
                'area' => $clean($delivery['area'] ?? null),
                'detail' => $clean($delivery['detail'] ?? null),
            ],
            'success_url' => $clean($this->input('success_url')),
            'cancel_url' => $clean($this->input('cancel_url')),
            'items' => $items,
        ]);
    }
}

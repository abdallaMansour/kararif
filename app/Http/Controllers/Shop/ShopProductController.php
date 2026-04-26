<?php

namespace App\Http\Controllers\Shop;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\ShopProduct;
use Illuminate\Http\JsonResponse;

class ShopProductController extends Controller
{
    public function index(): JsonResponse
    {
        $catalogSkus = ['book', 'uno-kharareef', 'stickers'];

        $products = ShopProduct::query()
            ->whereIn('sku', $catalogSkus)
            ->where('is_active', true)
            ->where('is_sellable', true)
            ->orderBy('id')
            ->get()
            ->map(fn (ShopProduct $product) => [
                'id' => (int) $product->id,
                'name_ar' => $product->name_ar,
                'price_aed' => (float) $product->price_aed,
                'image_url' => $product->image_url,
            ])
            ->values()
            ->all();

        return ApiResponse::success($products);
    }
}

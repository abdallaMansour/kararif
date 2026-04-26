<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\DashboardShopProductRequest;
use App\Models\ShopProduct;
use App\Traits\ApiTrait;
use Illuminate\Http\JsonResponse;

class DashboardShopProductController extends Controller
{
    use ApiTrait;

    public function index(): JsonResponse
    {
        $query = ShopProduct::query()->orderByDesc('id');

        if (request()->filled('q')) {
            $q = (string) request('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('sku', 'like', '%' . $q . '%')
                    ->orWhere('name_ar', 'like', '%' . $q . '%');
            });
        }

        if (request()->has('is_active')) {
            $query->where('is_active', filter_var(request('is_active'), FILTER_VALIDATE_BOOLEAN));
        }

        if (request()->has('is_sellable')) {
            $query->where('is_sellable', filter_var(request('is_sellable'), FILTER_VALIDATE_BOOLEAN));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) request('per_page', 20)),
        ]);
    }

    public function show(ShopProduct $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $product,
        ]);
    }

    public function create(DashboardShopProductRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['is_sellable'] = (bool) ($payload['is_sellable'] ?? true);

        ShopProduct::create($payload);

        return $this->sendSuccess(__('response.created'));
    }

    public function update(DashboardShopProductRequest $request, ShopProduct $product): JsonResponse
    {
        $product->update($request->validated());

        return $this->sendSuccess(__('response.updated'));
    }

    public function destroy(ShopProduct $product): JsonResponse
    {
        $product->delete();

        return $this->sendSuccess(__('response.deleted'));
    }
}

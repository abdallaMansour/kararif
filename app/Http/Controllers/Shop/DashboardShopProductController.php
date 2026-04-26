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

    private function serializeProduct(ShopProduct $product): array
    {
        return [
            'id' => $product->id,
            'sku' => $product->sku,
            'name_ar' => $product->name_ar,
            'price_aed' => (float) $product->price_aed,
            'image_url' => $product->resolvedImageUrl(),
            'is_active' => (bool) $product->is_active,
            'is_sellable' => (bool) $product->is_sellable,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

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

        $paginated = $query->paginate((int) request('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => [
                'current_page' => $paginated->currentPage(),
                'data' => collect($paginated->items())->map(
                    fn (ShopProduct $product) => $this->serializeProduct($product)
                )->values()->all(),
                'first_page_url' => $paginated->url(1),
                'from' => $paginated->firstItem(),
                'last_page' => $paginated->lastPage(),
                'last_page_url' => $paginated->url($paginated->lastPage()),
                'next_page_url' => $paginated->nextPageUrl(),
                'path' => $paginated->path(),
                'per_page' => $paginated->perPage(),
                'prev_page_url' => $paginated->previousPageUrl(),
                'to' => $paginated->lastItem(),
                'total' => $paginated->total(),
            ],
        ]);
    }

    public function show(ShopProduct $product): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->serializeProduct($product),
        ]);
    }

    public function create(DashboardShopProductRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $payload['is_active'] = (bool) ($payload['is_active'] ?? true);
        $payload['is_sellable'] = (bool) ($payload['is_sellable'] ?? true);

        unset($payload['image']);

        $product = ShopProduct::create($payload);
        if ($request->hasFile('image')) {
            $product->clearMediaCollection();
            $product->addMediaFromRequest('image')->toMediaCollection();
        }

        return response()->json([
            'success' => true,
            'message' => __('response.created'),
            'data' => $this->serializeProduct($product->fresh()),
        ]);
    }

    public function update(DashboardShopProductRequest $request, ShopProduct $product): JsonResponse
    {
        $payload = $request->validated();
        unset($payload['image']);
        $product->update($payload);

        if ($request->hasFile('image')) {
            $product->clearMediaCollection();
            $product->addMediaFromRequest('image')->toMediaCollection();
        }

        return response()->json([
            'success' => true,
            'message' => __('response.updated'),
            'data' => $this->serializeProduct($product->fresh()),
        ]);
    }

    public function destroy(ShopProduct $product): JsonResponse
    {
        $product->delete();

        return $this->sendSuccess(__('response.deleted'));
    }
}

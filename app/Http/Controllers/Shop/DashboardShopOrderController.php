<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shop\DashboardShopOrderStatusRequest;
use App\Models\ShopOrder;
use App\Services\Shop\ShopOrderMailService;
use App\Traits\ApiTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class DashboardShopOrderController extends Controller
{
    use ApiTrait;

    public function __construct(
        protected ShopOrderMailService $shopOrderMailService
    ) {}

    public function index(): JsonResponse
    {
        $query = ShopOrder::query()
            ->withCount('items')
            ->orderByDesc('id');

        if (request()->filled('status')) {
            $status = $this->normalizeStatus((string) request('status'));
            $query->where('status', $status);
        }

        if (request()->filled('q')) {
            $q = (string) request('q');
            $query->where(function ($builder) use ($q) {
                $builder->where('order_number', 'like', '%' . $q . '%')
                    ->orWhere('customer_full_name', 'like', '%' . $q . '%')
                    ->orWhere('customer_email', 'like', '%' . $q . '%')
                    ->orWhere('customer_phone', 'like', '%' . $q . '%');
            });
        }

        if (request()->filled('date_from')) {
            $query->whereDate('created_at', '>=', request('date_from'));
        }

        if (request()->filled('date_to')) {
            $query->whereDate('created_at', '<=', request('date_to'));
        }

        return response()->json([
            'success' => true,
            'data' => $query->paginate((int) request('per_page', 20)),
        ]);
    }

    private function normalizeStatus(string $status): string
    {
        if ($status === 'payment_pending') {
            return ShopOrder::STATUS_PENDING_PAYMENT;
        }

        return $status;
    }

    public function show(ShopOrder $order): JsonResponse
    {
        $order->load('items.product');

        return response()->json([
            'success' => true,
            'data' => $this->serializeOrder($order),
        ]);
    }

    public function updateStatus(DashboardShopOrderStatusRequest $request, ShopOrder $order): JsonResponse
    {
        if ($order->paid_at === null) {
            throw ValidationException::withMessages([
                'status' => ['Order payment is not completed yet.'],
            ]);
        }

        $status = (string) $request->validated()['status'];
        $previousStatus = (string) $order->status;

        $order->update([
            'status' => $status,
        ]);
        $order->refresh();
        $this->shopOrderMailService->sendStatusChangedMail($order, $previousStatus);

        return $this->sendSuccess(__('response.updated'));
    }

    private function serializeOrder(ShopOrder $order): array
    {
        return [
            'id' => $order->id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'customer_full_name' => $order->customer_full_name,
            'customer_phone' => $order->customer_phone,
            'customer_email' => $order->customer_email,
            'delivery_emirate' => $order->delivery_emirate,
            'delivery_area' => $order->delivery_area,
            'delivery_detail' => $order->delivery_detail,
            'subtotal_aed' => (float) $order->subtotal_aed,
            'shipping_fee_aed' => (float) $order->shipping_fee_aed,
            'total_aed' => (float) $order->total_aed,
            'paid_at' => $order->paid_at,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
            'items' => collect($order->items)->map(fn ($item) => [
                'id' => $item->id,
                'shop_product_id' => $item->shop_product_id,
                'name_ar' => $item->product?->name_ar,
                'quantity' => (int) $item->quantity,
                'unit_price_aed' => (float) $item->unit_price_aed,
                'line_total_aed' => (float) $item->line_total_aed,
                'signature_names' => $item->signature_names ?? [],
                'is_free_promotional_item' => $order->id <= 30
                    && (int) $item->shop_product_id === 3
                    && (float) $item->unit_price_aed === 0.0
                    && (float) $item->line_total_aed === 0.0,
            ])->values()->all(),
        ];
    }
}

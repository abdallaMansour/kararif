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
            ->whereNotNull('paid_at')
            ->withCount('items')
            ->orderByDesc('id');

        if (request()->filled('status')) {
            $query->where('status', (string) request('status'));
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

    public function show(ShopOrder $order): JsonResponse
    {
        $order->load('items.product');

        return response()->json([
            'success' => true,
            'data' => $order,
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
}

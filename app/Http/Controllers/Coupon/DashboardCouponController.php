<?php

namespace App\Http\Controllers\Coupon;

use App\Models\Coupon;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\CouponRequest;
use App\Http\Resources\Coupon\DashboardCouponResource;
use Illuminate\Http\JsonResponse;

class DashboardCouponController extends Controller
{
    use ApiTrait;

    public function index(): JsonResponse
    {
        $coupons = Coupon::orderBy('id')->get();
        return $this->sendResponse(DashboardCouponResource::collection($coupons)->resolve(), null);
    }

    public function show(Coupon $coupon): JsonResponse
    {
        return $this->sendResponse((new DashboardCouponResource($coupon))->resolve(), null);
    }

    public function store(CouponRequest $request): JsonResponse
    {
        try {
            $coupon = Coupon::create($request->validated());
            return $this->sendResponse((new DashboardCouponResource($coupon))->resolve(), __('response.created'), 201);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(CouponRequest $request, Coupon $coupon): JsonResponse
    {
        try {
            $coupon->update($request->validated());
            return $this->sendResponse((new DashboardCouponResource($coupon->fresh()))->resolve(), __('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        try {
            $coupon->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

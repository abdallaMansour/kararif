<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Payment;
use App\Models\PaymentPackage;
use App\Services\PaymentService;
use App\Services\RankPrizeService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function packages(): JsonResponse
    {
        $packages = PaymentPackage::where('active', true)->get()->map(fn ($p) => [
            'id' => (string) $p->id,
            'name' => $p->name,
            'points' => $p->points,
            'sessions_count' => (int) ($p->sessions_count ?? 0),
            'price' => (float) $p->price,
            'currency' => $p->currency,
            'icon' => '🎯',
        ])->values()->all();

        return ApiResponse::success($packages);
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $package = PaymentPackage::findOrFail($request->input('packageId'));
        /** @var \App\Models\User $user */
        $user = auth()->user();

        $amount = (float) $package->price;

        // Apply rank prize discount first (next 5 purchases by default)
        $rankPrizeService = app(RankPrizeService::class);
        if ($rankPrizeService->hasActiveDiscount($user)) {
            $amount = $rankPrizeService->applyDiscountAndConsumeOne($user, $amount);
        }

        $couponId = null;
        $couponCode = $request->input('couponCode');
        if ($couponCode) {
            $coupon = Coupon::where('code', $couponCode)->first();
            if ($coupon && $coupon->isActive() && !$coupon->isExpired()) {
                $usedCount = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
                if ($usedCount < $coupon->usage_per_user) {
                    $couponId = $coupon->id;
                    $amount = max(0, round($amount * (1 - (float) $coupon->discount_percentage / 100), 2));
                }
            }
        }

        $amount = max(0, $amount);
        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_package_id' => $package->id,
            'coupon_id' => $couponId,
            'payment_id' => 'pay_' . uniqid(),
            'status' => 'pending',
            'amount' => $amount,
        ]);

        return ApiResponse::success([
            'paymentId' => (string) $payment->id,
            'redirectUrl' => null,
        ], null, 200);
    }
}

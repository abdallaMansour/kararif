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
use App\Services\ZiinaService;
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
                $usedQuery = CouponUsage::where('coupon_id', $coupon->id);
                $usedQuery = $user instanceof \App\Models\Adventurer
                    ? $usedQuery->where('adventurer_id', $user->id)
                    : $usedQuery->where('user_id', $user->id);
                $usedCount = $usedQuery->count();
                if ($usedCount < $coupon->usage_per_user) {
                    $couponId = $coupon->id;
                    $amount = max(0, round($amount * (1 - (float) $coupon->discount_percentage / 100), 2));
                }
            }
        }

        $amount = max(0, $amount);

        $currency = config('ziina.currency', config('services.ziina.currency', 'AED'));
        $successUrl = config('ziina.success_url') ?: rtrim(config('app.url'), '/') . '/api/payment/success?payment_intent_id={PAYMENT_INTENT_ID}';
        $ziina = app(ZiinaService::class);
        $intent = $ziina->createPaymentIntent(
            (float) $amount,
            $currency,
            $successUrl,
            null,
            null,
            $package->name
        );

        $paymentIdExternal = null;
        $redirectUrl = null;
        if ($intent && ! empty($intent['redirect_url'])) {
            $paymentIdExternal = $intent['id'];
            $redirectUrl = $intent['redirect_url'];
        }

        $paymentData = [
            'payment_package_id' => $package->id,
            'coupon_id' => $couponId,
            'payment_id' => $paymentIdExternal,
            'status' => 'pending',
            'amount' => $amount,
        ];
        if ($user instanceof \App\Models\Adventurer) {
            $paymentData['adventurer_id'] = $user->id;
            $paymentData['user_id'] = null;
        } else {
            $paymentData['user_id'] = $user->id;
        }
        $payment = Payment::create($paymentData);

        return ApiResponse::success([
            'paymentId' => (string) $payment->id,
            'redirectUrl' => $redirectUrl,
        ], null, 200);
    }

    /**
     * Success callback: Ziina redirects the user here after payment.
     * Use this URL (with {PAYMENT_INTENT_ID} placeholder) as success_url when creating the intent.
     * Credits sessions and redirects user to frontend success page.
     */
    public function success(): \Illuminate\Http\RedirectResponse
    {
        $paymentIntentId = request()->query('payment_intent_id');
        if ($paymentIntentId) {
            app(PaymentService::class)->completePaymentByExternalId((string) $paymentIntentId);
        }

        $redirectUrl = config('ziina.payment_success_redirect_url', config('services.ziina.payment_success_redirect_url', '/'));

        return redirect()->away($redirectUrl);
    }
}

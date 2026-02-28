<?php

namespace App\Services;

use App\Models\CouponUsage;
use App\Models\Payment;

class PaymentService
{
    /**
     * Mark payment as completed and add package sessions to user.
     * Call this from Ziina webhook or when payment is confirmed.
     */
    public function completePayment(Payment $payment): void
    {
        if ($payment->status === 'completed') {
            return;
        }

        $payment->load('user', 'paymentPackage', 'coupon');
        $sessions = (int) ($payment->paymentPackage->sessions_count ?? 0);
        if ($sessions > 0) {
            $payment->user->increment('available_sessions', $sessions);
        }

        if ($payment->coupon_id) {
            CouponUsage::create([
                'user_id' => $payment->user_id,
                'coupon_id' => $payment->coupon_id,
                'used_at' => now(),
            ]);
        }

        $payment->update(['status' => 'completed']);
    }

    /**
     * Complete payment by external payment_id (e.g. from Ziina webhook).
     */
    public function completePaymentByExternalId(string $externalPaymentId): ?Payment
    {
        $payment = Payment::where('payment_id', $externalPaymentId)->first();
        if ($payment) {
            $this->completePayment($payment);
            return $payment;
        }
        return null;
    }
}

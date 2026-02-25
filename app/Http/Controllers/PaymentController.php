<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Http\Requests\Payment\InitiatePaymentRequest;
use App\Models\Payment;
use App\Models\PaymentPackage;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function packages(): JsonResponse
    {
        $packages = PaymentPackage::where('active', true)->get()->map(fn ($p) => [
            'id' => (string) $p->id,
            'name' => $p->name,
            'points' => $p->points,
            'price' => (float) $p->price,
            'currency' => $p->currency,
            'icon' => '🎯',
        ])->values()->all();

        return ApiResponse::success($packages);
    }

    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        $package = PaymentPackage::findOrFail($request->input('packageId'));
        $user = auth()->user();

        $payment = Payment::create([
            'user_id' => $user->id,
            'payment_package_id' => $package->id,
            'payment_id' => 'pay_' . uniqid(),
            'status' => 'pending',
            'amount' => $package->price,
        ]);

        return ApiResponse::success([
            'paymentId' => (string) $payment->id,
            'redirectUrl' => null,
        ], null, 200);
    }
}

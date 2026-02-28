<?php

namespace App\Http\Controllers\Coupon;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Coupon\ApplyCouponRequest;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\PaymentPackage;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        $code = $request->input('code');
        $packageId = $request->input('packageId');

        $coupon = Coupon::where('code', $code)->first();
        if (!$coupon) {
            return ApiResponse::error('كود الخصم غير صالح', 400);
        }
        if (!$coupon->isActive()) {
            return ApiResponse::error('كود الخصم غير نشط', 400);
        }
        if ($coupon->isExpired()) {
            return ApiResponse::error('انتهت صلاحية كود الخصم', 400);
        }

        $user = auth()->user();
        $usedCount = CouponUsage::where('coupon_id', $coupon->id)->where('user_id', $user->id)->count();
        if ($usedCount >= $coupon->usage_per_user) {
            return ApiResponse::error('استخدمت هذا الكود الحد المسموح له', 400);
        }

        $originalPrice = 0;
        $sessionsCount = 0;
        if ($packageId) {
            $package = PaymentPackage::find($packageId);
            if ($package) {
                $originalPrice = (float) $package->price;
                $sessionsCount = (int) ($package->sessions_count ?? 0);
            }
        }

        $discountPercent = (float) $coupon->discount_percentage;
        $discountedPrice = round($originalPrice * (1 - $discountPercent / 100), 2);

        return ApiResponse::success([
            'valid' => true,
            'coupon_id' => (string) $coupon->id,
            'discount_percentage' => $discountPercent,
            'original_price' => $originalPrice,
            'discounted_price' => $discountedPrice,
            'sessions_count' => $sessionsCount,
        ]);
    }
}

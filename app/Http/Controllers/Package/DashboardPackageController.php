<?php

namespace App\Http\Controllers\Package;

use App\Models\PaymentPackage;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Package\PackageRequest;
use App\Http\Resources\Package\DashboardPackageResource;
use Illuminate\Http\JsonResponse;

class DashboardPackageController extends Controller
{
    use ApiTrait;

    public function index(): JsonResponse
    {
        $packages = PaymentPackage::orderBy('id')->get();
        return $this->sendResponse(DashboardPackageResource::collection($packages)->resolve(), null);
    }

    public function show(PaymentPackage $payment_package): JsonResponse
    {
        return $this->sendResponse((new DashboardPackageResource($payment_package))->resolve(), null);
    }

    public function store(PackageRequest $request): JsonResponse
    {
        try {
            $package = PaymentPackage::create($request->validated());
            return $this->sendResponse((new DashboardPackageResource($package))->resolve(), __('response.created'), 201);
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(PackageRequest $request, PaymentPackage $payment_package): JsonResponse
    {
        try {
            $payment_package->update($request->validated());
            return $this->sendResponse((new DashboardPackageResource($payment_package->fresh()))->resolve(), __('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(PaymentPackage $payment_package): JsonResponse
    {
        try {
            $payment_package->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

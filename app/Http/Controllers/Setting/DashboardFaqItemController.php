<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\ApiResponse;
use App\Models\FaqItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\FaqItemRequest;
use Illuminate\Http\JsonResponse;

class DashboardFaqItemController extends Controller
{
    public function index(): JsonResponse
    {
        $items = FaqItem::orderBy('order')->orderBy('id')->get();
        return response()->json(['data' => $items]);
    }

    public function store(FaqItemRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['order'] = $data['order'] ?? FaqItem::max('order') + 1;
        $item = FaqItem::create($data);
        return ApiResponse::success($item, __('response.created'), 201);
    }

    public function show(FaqItem $faqItem): JsonResponse
    {
        return response()->json(['data' => $faqItem]);
    }

    public function update(FaqItemRequest $request, FaqItem $faqItem): JsonResponse
    {
        $faqItem->update($request->validated());
        return ApiResponse::success(null, __('response.updated'), 200);
    }

    public function destroy(FaqItem $faqItem): JsonResponse
    {
        $faqItem->delete();
        return ApiResponse::success(null, __('response.deleted'), 200);
    }
}

<?php

namespace App\Http\Controllers\Game;

use App\Helpers\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\TvDisplay;
use App\Services\GameService;
use Illuminate\Http\JsonResponse;

class TvDisplayController extends Controller
{
    public function __construct(
        protected GameService $gameService
    ) {}

    public function getOrCreateCode(\Illuminate\Http\Request $request): JsonResponse
    {
        $deviceId = $request->input('deviceId');
        if (empty($deviceId) || !is_string($deviceId)) {
            return ApiResponse::error('deviceId مطلوب', 400);
        }

        $display = $this->gameService->getOrCreateTvDisplay($deviceId);

        return ApiResponse::success([
            'displayId' => (string) $display->id,
            'code' => $display->code,
            'expiresAt' => $display->expires_at?->toIso8601String(),
        ]);
    }

    public function getDisplayStatus(int $displayId): JsonResponse
    {
        $display = TvDisplay::find($displayId);
        if (!$display) {
            return ApiResponse::error('شاشة التلفزيون غير موجودة', 404);
        }

        return $this->formatDisplayStatus($display);
    }

    public function getDisplayStatusByCode(string $code): JsonResponse
    {
        $display = TvDisplay::where('code', $code)->first();
        if (!$display) {
            return ApiResponse::error('رمز شاشة التلفزيون غير صحيح', 404);
        }

        return $this->formatDisplayStatus($display);
    }

    private function formatDisplayStatus(TvDisplay $display): JsonResponse
    {
        $linked = $display->status === TvDisplay::STATUS_LINKED && $display->room_id;
        $roomId = $linked ? (string) $display->room_id : null;
        $sessionId = null;

        if ($linked && $display->room) {
            $session = $display->room->gameSessions()
                ->whereIn('status', ['waiting', 'playing', 'starting', 'paused'])
                ->latest()
                ->first();
            $sessionId = $session ? (string) $session->id : null;
        }

        return ApiResponse::success([
            'linked' => $linked,
            'roomId' => $roomId,
            'sessionId' => $sessionId,
            'status' => $display->status,
        ]);
    }
}

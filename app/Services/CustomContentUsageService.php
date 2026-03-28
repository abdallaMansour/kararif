<?php

namespace App\Services;

use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Models\GameSession;
use Illuminate\Support\Facades\DB;

class CustomContentUsageService
{
    /**
     * When a custom-room session finishes: bump category usage once and each scheduled custom question once.
     */
    public function recordFinishedCustomSession(GameSession $session): void
    {
        $session->loadMissing('room');
        $room = $session->room;

        if (! $room || ! $room->is_custom || ! $room->custom_category_id) {
            return;
        }

        $categoryId = (int) $room->custom_category_id;
        $questionIds = $session->question_ids ?? [];
        if (! is_array($questionIds)) {
            $questionIds = [];
        }

        DB::transaction(function () use ($categoryId, $questionIds) {
            CustomCategory::whereKey($categoryId)->increment('usage_count');

            foreach ($questionIds as $rawId) {
                $qid = (int) $rawId;
                if ($qid <= 0) {
                    continue;
                }
                CustomQuestion::query()
                    ->whereKey($qid)
                    ->where('custom_category_id', $categoryId)
                    ->increment('usage_count');
            }
        });
    }
}

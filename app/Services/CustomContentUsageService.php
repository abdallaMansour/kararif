<?php

namespace App\Services;

use App\Models\CustomCategory;
use App\Models\CustomQuestion;
use App\Models\GameSession;
use Illuminate\Support\Facades\DB;

class CustomContentUsageService
{
    /**
     * Custom room: increment category usage once when the session moves to playing (session start).
     */
    public function recordCustomCategorySessionStart(GameSession $session): void
    {
        $session->loadMissing('room');
        $room = $session->room;

        if (! $room || ! $room->is_custom || ! $room->custom_category_id) {
            return;
        }

        CustomCategory::whereKey((int) $room->custom_category_id)->increment('usage_count');
    }

    /**
     * Custom room: increment usage for the custom question currently shown (session.current_round).
     */
    public function recordCustomQuestionShown(GameSession $session): void
    {
        $session->loadMissing('room');
        $room = $session->room;

        if (! $room || ! $room->is_custom || ! $room->custom_category_id) {
            return;
        }

        $categoryId = (int) $room->custom_category_id;
        $questionIds = $session->question_ids ?? [];
        if (! is_array($questionIds)) {
            return;
        }

        $idx = (int) $session->current_round - 1;
        if (! isset($questionIds[$idx])) {
            return;
        }

        $qid = (int) $questionIds[$idx];
        if ($qid <= 0) {
            return;
        }

        DB::transaction(function () use ($categoryId, $qid) {
            CustomQuestion::query()
                ->whereKey($qid)
                ->where('custom_category_id', $categoryId)
                ->increment('usage_count');
        });
    }
}

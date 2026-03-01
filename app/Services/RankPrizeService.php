<?php

namespace App\Services;

use App\Helpers\RankHelper;
use App\Models\User;

class RankPrizeService
{
    /**
     * Sync user's rank prizes: grant discount uses (next 5 purchases) and free sessions
     * when they reach a rank that has those prizes, once per rank.
     */
    public function syncUserRankPrizes(User $user): void
    {
        $score = (float) ($user->points ?? 0);
        $rankData = RankHelper::getRankForScore($score);
        if (! $rankData) {
            return;
        }

        $rankId = (int) $rankData['id'];
        $prizeType = $rankData['prize_type'] ?? null;
        $prizeValue = isset($rankData['prize_value']) ? (int) $rankData['prize_value'] : 0;

        $grantedDiscountIds = $user->granted_discount_rank_ids ?? [];
        $grantedSessionIds = $user->granted_session_rank_ids ?? [];

        // Grant discount (next 5 purchases at prize_value %) once per rank
        if ($prizeType === 'discount_next_5_purchases' && $prizeValue >= 0) {
            if (! in_array($rankId, $grantedDiscountIds, true)) {
                $grantedDiscountIds[] = $rankId;
                $user->rank_discount_percent = $prizeValue;
                $user->rank_discount_uses_left = 5;
                $user->granted_discount_rank_ids = $grantedDiscountIds;
            }
        }

        // Grant free sessions once per rank
        if ($prizeType === 'free_sessions' && $prizeValue > 0) {
            if (! in_array($rankId, $grantedSessionIds, true)) {
                $grantedSessionIds[] = $rankId;
                $user->available_sessions = (int) ($user->available_sessions ?? 0) + $prizeValue;
                $user->granted_session_rank_ids = $grantedSessionIds;
            }
        }

        $user->save();
    }

    /**
     * Whether the user has an active rank discount that can be applied.
     */
    public function hasActiveDiscount(User $user): bool
    {
        return ($user->rank_discount_uses_left ?? 0) > 0
            && ($user->rank_discount_percent ?? 0) > 0;
    }

    /**
     * Apply rank discount to an amount and consume one use. Returns the discounted amount.
     */
    public function applyDiscountAndConsumeOne(User $user, float $amount): float
    {
        if (! $this->hasActiveDiscount($user)) {
            return $amount;
        }
        $percent = (int) $user->rank_discount_percent;
        $discounted = round($amount * (1 - $percent / 100), 2);
        $user->rank_discount_uses_left = max(0, ($user->rank_discount_uses_left ?? 0) - 1);
        if ($user->rank_discount_uses_left === 0) {
            $user->rank_discount_percent = null;
        }
        $user->save();
        return $discounted;
    }
}

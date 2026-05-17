<?php

namespace App\Helpers;

use App\Models\Adventurer;
use App\Models\Rank;
use App\Models\User;
use App\Services\UserService;

class RankHelper
{
    /**
     * Score used for rank tiers (matches profile "winnings" / full game wins).
     * Not the same as {@see User::$points}, which tracks level progression (+1/-1 per game).
     */
    public static function getRankScoreForParticipant(User|Adventurer $user): float
    {
        if ($user instanceof Adventurer) {
            $lifetime = (float) ($user->lifetime_score ?? 0);
            if ($lifetime > 0) {
                return $lifetime;
            }
        }

        return (float) app(UserService::class)->getWinsLosses($user)['wins'];
    }

    public static function getRankForParticipant(User|Adventurer $user): ?array
    {
        return self::getRankForScore(self::getRankScoreForParticipant($user));
    }

    public static function getRankForScore(float $score): ?array
    {
        $rank = Rank::orderBy('start_score', 'desc')
            ->where('start_score', '<=', $score)
            ->first();
        if (!$rank) {
            return null;
        }
        $next = Rank::where('start_score', '>', $rank->start_score)->orderBy('start_score')->first();
        $endScore = $next ? $next->start_score - 1 : null;
        if ($endScore !== null && $score > $endScore) {
            return null;
        }
        return [
            'id' => $rank->id,
            'name' => $rank->name,
            'start_score' => $rank->start_score,
            'end_score' => $endScore,
            'icon' => $rank->getFirstMediaUrl(),
            'prize_type' => $rank->prize_type,
            'prize_value' => $rank->prize_value,
            'prize_label' => $rank->prize_label_ar,
        ];
    }
}

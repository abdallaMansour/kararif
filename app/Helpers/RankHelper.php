<?php

namespace App\Helpers;

use App\Models\Rank;

class RankHelper
{
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
        ];
    }
}

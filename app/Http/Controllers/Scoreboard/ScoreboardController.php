<?php

namespace App\Http\Controllers\Scoreboard;

use App\Helpers\ApiResponse;
use App\Models\Adventurer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Scoreboard\ScoreboardEntryResource;
use Illuminate\Http\JsonResponse;

class ScoreboardController extends Controller
{
    public function index(): JsonResponse
    {
        $perPage = (int) request('per_page', 50);
        $countryCode = request('country_code');

        $query = Adventurer::orderBy('lifetime_score', 'desc');
        if ($countryCode && $countryCode !== 'all') {
            $query->where('country_code', $countryCode);
        }
        $adventurers = $query->limit($perPage)->get();

        $all = ScoreboardEntryResource::collection($adventurers)->resolve();
        $top3 = array_slice($all, 0, 3);
        $rest = array_slice($all, 3);

        return ApiResponse::success([
            'top3' => $top3,
            'rest' => $rest,
            'total' => count($all),
        ]);
    }
}

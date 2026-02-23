<?php

namespace App\Http\Controllers\Scoreboard;

use App\Models\Adventurer;
use App\Http\Controllers\Controller;
use App\Http\Resources\Scoreboard\ScoreboardEntryResource;

class ScoreboardController extends Controller
{
    public function index()
    {
        $perPage = request('per_page', 50);
        $adventurers = Adventurer::orderBy('lifetime_score', 'desc')->paginate($perPage);
        return ScoreboardEntryResource::collection($adventurers);
    }
}

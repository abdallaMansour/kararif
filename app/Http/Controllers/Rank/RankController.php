<?php

namespace App\Http\Controllers\Rank;

use App\Models\Rank;
use App\Http\Controllers\Controller;
use App\Http\Resources\Rank\RankResource;

class RankController extends Controller
{
    public function index()
    {
        return RankResource::collection(Rank::orderBy('start_score')->get());
    }
}

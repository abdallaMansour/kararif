<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Stage;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\StageResource;

class StageController extends Controller
{
    public function index()
    {
        return StageResource::collection(Stage::where('status', true)->get());
    }

    public function show(Stage $stage)
    {
        return new StageResource($stage);
    }
}

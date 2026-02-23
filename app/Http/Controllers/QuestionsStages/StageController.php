<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Stage;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\StageResource;

class StageController extends Controller
{
    public function index()
    {
        return StageResource::collection(Stage::with('questionGroups')->where('status', true)->get());
    }

    public function show(Stage $stage)
    {
        $stage->load('questionGroups');
        return new StageResource($stage);
    }
}

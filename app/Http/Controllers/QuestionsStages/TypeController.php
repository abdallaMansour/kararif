<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Type;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\TypeResource;

class TypeController extends Controller
{
    public function index()
    {
        return TypeResource::collection(Type::where('status', true)->withCount('categories')->get());
    }

    public function show(Type $type)
    {
        return new TypeResource($type);
    }
}

<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Type;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\TypeResource;

class TypeController extends Controller
{
    public function index()
    {
        $query = Type::where('status', true);
        if (request()->has('stage_id')) {
            $query->where('stage_id', request('stage_id'));
        }
        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        }
        if (request()->has('subcategory_id')) {
            $query->where('subcategory_id', request('subcategory_id'));
        }
        return TypeResource::collection($query->get());
    }

    public function show(Type $type)
    {
        return new TypeResource($type);
    }
}

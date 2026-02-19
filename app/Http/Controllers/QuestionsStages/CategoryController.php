<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\CategoryResource;

class CategoryController extends Controller
{
    public function index()
    {
        $stageId = request()->get('stage_id');
        $query = Category::where('status', true);
        if ($stageId) {
            $query->where('stage_id', $stageId);
        }
        return CategoryResource::collection($query->get());
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }
}

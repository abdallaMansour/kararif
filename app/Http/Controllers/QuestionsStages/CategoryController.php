<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Category;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\CategoryResource;

class CategoryController extends Controller
{
    public function index()
    {
        $typeId = request()->get('type_id');
        $query = Category::where('status', true);
        if ($typeId) {
            $query->where('type_id', $typeId);
        }
        return CategoryResource::collection($query->get());
    }

    public function show(Category $category)
    {
        return new CategoryResource($category);
    }
}

<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Subcategory;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\SubcategoryResource;

class SubcategoryController extends Controller
{
    public function index()
    {
        $query = Subcategory::where('status', true)->with('category')->withCount('questions');
        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        }
        return SubcategoryResource::collection($query->get());
    }

    public function show(Subcategory $subcategory)
    {
        $subcategory->load('category')->loadCount('questions');
        return new SubcategoryResource($subcategory);
    }
}

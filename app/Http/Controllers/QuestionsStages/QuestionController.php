<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Question;
use App\Http\Controllers\Controller;
use App\Http\Resources\QuestionsStages\QuestionResource;

class QuestionController extends Controller
{
    public function index()
    {
        $query = Question::where('status', true);
        if (request()->has('stage_id')) {
            $query->where('stage_id', request('stage_id'));
        }
        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        }
        if (request()->has('subcategory_id')) {
            $query->where('subcategory_id', request('subcategory_id'));
        }
        if (request()->has('type_id')) {
            $query->where('type_id', request('type_id'));
        }
        return QuestionResource::collection($query->get());
    }

    public function show(Question $question)
    {
        return new QuestionResource($question);
    }
}

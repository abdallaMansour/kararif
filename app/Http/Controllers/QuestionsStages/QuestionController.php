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
        if (request()->has('type_id')) {
            $query->where('type_id', request('type_id'));
        }
        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        }
        if (request()->has('subcategory_id')) {
            $query->where('subcategory_id', request('subcategory_id'));
        }
        $perPage = min((int) request('per_page', 15), 100);
        return QuestionResource::collection($query->with('type')->paginate($perPage));
    }

    public function show(Question $question)
    {
        $question->load('type');
        return new QuestionResource($question);
    }
}

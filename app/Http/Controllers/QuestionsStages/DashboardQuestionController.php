<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Question;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\QuestionRequest;
use App\Http\Resources\QuestionsStages\DashboardQuestionResource;

class DashboardQuestionController extends Controller
{
    use ApiTrait;

    public function index()
    {
        $query = Question::query();
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
        return DashboardQuestionResource::collection($query->get());
    }

    public function show(Question $question)
    {
        return new DashboardQuestionResource($question);
    }

    public function create(QuestionRequest $request)
    {
        try {
            Question::create($request->validated());
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(QuestionRequest $request, Question $question)
    {
        try {
            $question->update($request->validated());
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Question $question)
    {
        try {
            $question->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Question;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\QuestionRequest;
use App\Http\Requests\QuestionsStages\QuestionVideosRequest;
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
            $question->clearMediaCollection('start_video');
            $question->clearMediaCollection('lunch_video');
            $question->clearMediaCollection('question_video');
            $question->clearMediaCollection('correct_answer_video');
            $question->clearMediaCollection('wrong_answer_video');
            $question->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    /**
     * Show question with video URLs (for assign-videos page).
     */
    public function showVideos(Question $question)
    {
        return new DashboardQuestionResource($question);
    }

    /**
     * Update the 5 videos for a question.
     */
    public function updateVideos(QuestionVideosRequest $request, Question $question)
    {
        try {
            $collections = [
                'start_video',
                'lunch_video',
                'question_video',
                'correct_answer_video',
                'wrong_answer_video',
            ];
            foreach ($collections as $collection) {
                if ($request->hasFile($collection)) {
                    $question->clearMediaCollection($collection);
                    $question->addMediaFromRequest($collection)->toMediaCollection($collection);
                }
            }
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

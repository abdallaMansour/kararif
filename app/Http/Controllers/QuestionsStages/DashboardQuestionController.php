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
        return DashboardQuestionResource::collection($query->with('type')->paginate($perPage));
    }

    public function show(Question $question)
    {
        $question->load('type');
        return new DashboardQuestionResource($question);
    }

    public function create(QuestionRequest $request)
    {
        try {
            $data = $request->validated();
            $kind = $data['question_kind'] ?? Question::KIND_NORMAL;
            if ($kind === Question::KIND_WORDS) {
                $data['answer_1'] = $data['answer_2'] = $data['answer_3'] = $data['answer_4'] = '';
                $data['is_correct_1'] = $data['is_correct_2'] = $data['is_correct_3'] = $data['is_correct_4'] = false;
            }
            $question = Question::create($data);

            if ($request->hasFile('image')) {
                $question->clearMediaCollection('image');
                $question->addMediaFromRequest('image')->toMediaCollection('image');
            }
            if ($request->hasFile('voice')) {
                $question->clearMediaCollection('voice');
                $question->addMediaFromRequest('voice')->toMediaCollection('voice');
            }
            if ($request->hasFile('video')) {
                $question->clearMediaCollection('video');
                $question->addMediaFromRequest('video')->toMediaCollection('video');
            }
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(QuestionRequest $request, Question $question)
    {
        try {
            $data = $request->validated();
            $kind = $data['question_kind'] ?? $question->question_kind;
            if ($kind === Question::KIND_WORDS) {
                $data['answer_1'] = $data['answer_2'] = $data['answer_3'] = $data['answer_4'] = '';
                $data['is_correct_1'] = $data['is_correct_2'] = $data['is_correct_3'] = $data['is_correct_4'] = false;
            }
            $question->update($data);

            if ($request->hasFile('image')) {
                $question->clearMediaCollection('image');
                $question->addMediaFromRequest('image')->toMediaCollection('image');
            } elseif ($request->has('image') && $request->input('image') === null) {
                $question->clearMediaCollection('image');
            }
            if ($request->hasFile('voice')) {
                $question->clearMediaCollection('voice');
                $question->addMediaFromRequest('voice')->toMediaCollection('voice');
            } elseif ($request->has('voice') && $request->input('voice') === null) {
                $question->clearMediaCollection('voice');
            }
            if ($request->hasFile('video')) {
                $question->clearMediaCollection('video');
                $question->addMediaFromRequest('video')->toMediaCollection('video');
            } elseif ($request->has('video') && $request->input('video') === null) {
                $question->clearMediaCollection('video');
            }
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
            $question->clearMediaCollection('image');
            $question->clearMediaCollection('voice');
            $question->clearMediaCollection('video');
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

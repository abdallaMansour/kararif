<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Stage;
use App\Models\StageQuestionGroup;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\StageRequest;
use App\Http\Resources\QuestionsStages\DashboardStageResource;
use Illuminate\Support\Facades\DB;

class DashboardStageController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardStageResource::collection(Stage::with('questionGroups')->get());
    }

    public function show(Stage $stage)
    {
        $stage->load('questionGroups');
        return new DashboardStageResource($stage);
    }

    public function create(StageRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $files = ['back_icon', 'home_icon', 'exit_icon', 'start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'];
            foreach ($files as $f) {
                unset($data[$f]);
            }
            $stage = Stage::create($data);

            if ($request->hasFile('back_icon')) {
                $stage->addMediaFromRequest('back_icon')->toMediaCollection('back_icon');
            }
            if ($request->hasFile('home_icon')) {
                $stage->addMediaFromRequest('home_icon')->toMediaCollection('home_icon');
            }
            if ($request->hasFile('exit_icon')) {
                $stage->addMediaFromRequest('exit_icon')->toMediaCollection('exit_icon');
            }
            if ($stage->stage_type === Stage::TYPE_LIFE_POINTS) {
                foreach (['start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'] as $col) {
                    if ($request->hasFile($col)) {
                        $stage->addMediaFromRequest($col)->toMediaCollection($col);
                    }
                }
            }
            if ($stage->stage_type === Stage::TYPE_QUESTIONS_GROUP && $stage->question_groups_count) {
                for ($i = 0; $i < (int) $stage->question_groups_count; $i++) {
                    StageQuestionGroup::create(['stage_id' => $stage->id, 'sort_order' => $i]);
                }
            }

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(StageRequest $request, Stage $stage)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            $files = ['back_icon', 'home_icon', 'exit_icon', 'start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'];
            foreach ($files as $f) {
                unset($data[$f]);
            }
            $stage->update($data);

            if ($stage->stage_type === Stage::TYPE_LIFE_POINTS) {
                $stage->questionGroups()->each(function ($g) {
                    $g->clearMediaCollection('start_video');
                    $g->clearMediaCollection('end_video');
                    $g->clearMediaCollection('correct_answer_video');
                    $g->clearMediaCollection('wrong_answer_video');
                    $g->delete();
                });
            }
            if ($stage->stage_type === Stage::TYPE_QUESTIONS_GROUP) {
                foreach (['start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'] as $col) {
                    $stage->clearMediaCollection($col);
                }
            }

            if ($request->hasFile('back_icon')) {
                $stage->clearMediaCollection('back_icon');
                $stage->addMediaFromRequest('back_icon')->toMediaCollection('back_icon');
            }
            if ($request->hasFile('home_icon')) {
                $stage->clearMediaCollection('home_icon');
                $stage->addMediaFromRequest('home_icon')->toMediaCollection('home_icon');
            }
            if ($request->hasFile('exit_icon')) {
                $stage->clearMediaCollection('exit_icon');
                $stage->addMediaFromRequest('exit_icon')->toMediaCollection('exit_icon');
            }
            if ($stage->stage_type === Stage::TYPE_LIFE_POINTS) {
                foreach (['start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'] as $col) {
                    if ($request->hasFile($col)) {
                        $stage->clearMediaCollection($col);
                        $stage->addMediaFromRequest($col)->toMediaCollection($col);
                    }
                }
            }
            if ($stage->stage_type === Stage::TYPE_QUESTIONS_GROUP && (int) $stage->question_groups_count > 0) {
                $current = $stage->questionGroups()->count();
                $wanted = (int) $stage->question_groups_count;
                if ($wanted > $current) {
                    for ($i = $current; $i < $wanted; $i++) {
                        StageQuestionGroup::create(['stage_id' => $stage->id, 'sort_order' => $i]);
                    }
                } elseif ($wanted < $current) {
                    $stage->questionGroups()->orderBy('sort_order')->skip($wanted)->take($current - $wanted)->get()->each(function ($g) {
                        $g->clearMediaCollection('start_video');
                        $g->clearMediaCollection('end_video');
                        $g->clearMediaCollection('correct_answer_video');
                        $g->clearMediaCollection('wrong_answer_video');
                        $g->delete();
                    });
                }
            }

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Stage $stage)
    {
        try {
            $stage->clearMediaCollection('back_icon');
            $stage->clearMediaCollection('home_icon');
            $stage->clearMediaCollection('exit_icon');
            foreach (['start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'] as $col) {
                $stage->clearMediaCollection($col);
            }
            foreach ($stage->questionGroups as $g) {
                $g->clearMediaCollection('start_video');
                $g->clearMediaCollection('end_video');
                $g->clearMediaCollection('correct_answer_video');
                $g->clearMediaCollection('wrong_answer_video');
            }
            $stage->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function updateGroupVideos(\Illuminate\Http\Request $request, Stage $stage, StageQuestionGroup $group)
    {
        $request->validate([
            'start_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'end_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'correct_answer_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
            'wrong_answer_video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime'],
        ]);
        if ($group->stage_id !== $stage->id) {
            return $this->sendError('Invalid group for this stage.', [], 400);
        }
        try {
            foreach (['start_video', 'end_video', 'correct_answer_video', 'wrong_answer_video'] as $col) {
                if ($request->hasFile($col)) {
                    $group->clearMediaCollection($col);
                    $group->addMediaFromRequest($col)->toMediaCollection($col);
                }
            }
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

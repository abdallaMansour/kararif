<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\CustomStageRequest;
use App\Http\Resources\QuestionsStages\DashboardCustomStageResource;
use App\Models\CustomStage;
use App\Traits\ApiTrait;
use Illuminate\Support\Facades\DB;

class DashboardCustomStageController extends Controller
{
    use ApiTrait;

    private const VIDEO_FIELDS = [
        'start_video',
        'end_video',
        'lunch_video',
        'correct_answer_video',
        'wrong_answer_video',
    ];

    public function index()
    {
        return DashboardCustomStageResource::collection(
            CustomStage::query()->orderBy('id')->get()
        );
    }

    public function show(CustomStage $custom_stage)
    {
        return new DashboardCustomStageResource($custom_stage);
    }

    public function create(CustomStageRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['cover_image'], $data['start_video'], $data['end_video'], $data['lunch_video'], $data['correct_answer_video'], $data['wrong_answer_video']);

            $customStage = CustomStage::create($data);

            if ($request->hasFile('cover_image')) {
                $customStage->addMediaFromRequest('cover_image')->toMediaCollection('cover_image');
            }
            foreach (self::VIDEO_FIELDS as $col) {
                if ($request->hasFile($col)) {
                    $customStage->addMediaFromRequest($col)->toMediaCollection($col);
                }
            }

            DB::commit();

            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();

            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(CustomStageRequest $request, CustomStage $custom_stage)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['cover_image'], $data['start_video'], $data['end_video'], $data['lunch_video'], $data['correct_answer_video'], $data['wrong_answer_video']);

            $custom_stage->update($data);

            if ($request->hasFile('cover_image')) {
                $custom_stage->clearMediaCollection('cover_image');
                $custom_stage->addMediaFromRequest('cover_image')->toMediaCollection('cover_image');
            }
            foreach (self::VIDEO_FIELDS as $col) {
                if ($request->hasFile($col)) {
                    $custom_stage->clearMediaCollection($col);
                    $custom_stage->addMediaFromRequest($col)->toMediaCollection($col);
                }
            }

            DB::commit();

            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();

            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(CustomStage $custom_stage)
    {
        try {
            $custom_stage->clearMediaCollection('cover_image');
            foreach (self::VIDEO_FIELDS as $col) {
                $custom_stage->clearMediaCollection($col);
            }
            $custom_stage->delete();

            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

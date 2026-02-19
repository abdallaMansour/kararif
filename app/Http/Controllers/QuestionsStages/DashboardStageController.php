<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Stage;
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
        return DashboardStageResource::collection(Stage::all());
    }

    public function show(Stage $stage)
    {
        return new DashboardStageResource($stage);
    }

    public function create(StageRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['back_icon'], $data['home_icon'], $data['exit_icon']);
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
            unset($data['back_icon'], $data['home_icon'], $data['exit_icon']);
            $stage->update($data);

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
            $stage->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

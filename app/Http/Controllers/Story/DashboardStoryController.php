<?php

namespace App\Http\Controllers\Story;

use App\Models\Story;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Story\StoryRequest;
use App\Http\Resources\Story\DashboardStoryResource;
use Illuminate\Support\Facades\DB;

class DashboardStoryController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardStoryResource::collection(Story::all());
    }

    public function show(Story $story)
    {
        return new DashboardStoryResource($story);
    }

    public function create(StoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $story = Story::create($request->all());

            if ($request->hasFile('image')) {
                $story->addMediaFromRequest('image')->toMediaCollection();
            }

            $story->save();

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), code: 500);
        }
    }

    public function update(StoryRequest $request, Story $story)
    {
        try {
            DB::beginTransaction();

            $story->update($request->all());

            if ($request->hasFile('image')) {
                $story->clearMediaCollection();
                $story->addMediaFromRequest('image')->toMediaCollection();
            }

            $story->save();

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), 500);
        }
    }

    public function destroy(Story $story)
    {
        try {
            $story->delete();
            $story->clearMediaCollection();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), 500);
        }
    }
}

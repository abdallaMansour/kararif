<?php

namespace App\Http\Controllers\FullStory;

use App\Models\FullStory;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\FullStory\FullStoryRequest;
use App\Http\Resources\FullStory\DashboardFullStoryResource;
use Illuminate\Support\Facades\DB;

class DashboardFullStoryController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardFullStoryResource::collection(FullStory::all());
    }

    public function show(FullStory $full_story)
    {
        return new DashboardFullStoryResource($full_story);
    }

    public function create(FullStoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $full_story = FullStory::create($request->all());

            if ($request->hasFile('audios')) {
                foreach ($request->file('audios') as $audio) {
                    $full_story->addMedia($audio)->toMediaCollection('audios');
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    $full_story->addMedia($video)->toMediaCollection('videos');
                }
            }

            $full_story->save();

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(FullStoryRequest $request, FullStory $full_story)
    {
        try {
            DB::beginTransaction();

            $full_story->update($request->all());

            if ($request->hasFile('audios')) {
                $full_story->clearMediaCollection('audios');
                foreach ($request->file('audios') as $audio) {
                    $full_story->addMedia($audio)->toMediaCollection('audios');
                }
            }

            if ($request->hasFile('videos')) {
                $full_story->clearMediaCollection('videos');
                foreach ($request->file('videos') as $video) {
                    $full_story->addMedia($video)->toMediaCollection('videos');
                }
            }

            $full_story->save();

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(FullStory $full_story)
    {
        try {
            $full_story->delete();
            $full_story->clearMediaCollection('audios');
            $full_story->clearMediaCollection('videos');
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

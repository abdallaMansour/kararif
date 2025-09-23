<?php

namespace App\Http\Controllers\Toy;

use App\Models\Toy;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Toy\ToyRequest;
use App\Http\Resources\Toy\DashboardToyResource;
use Illuminate\Support\Facades\DB;

class DashboardToyController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardToyResource::collection(Toy::all());
    }

    public function show(Toy $toy)
    {
        return new DashboardToyResource($toy);
    }

    public function create(ToyRequest $request)
    {
        try {
            DB::beginTransaction();
            $toy = Toy::create($request->all());

            if ($request->hasFile('audios')) {
                foreach ($request->file('audios') as $audio) {
                    $toy->addMedia($audio)->toMediaCollection('audios');
                }
            }

            if ($request->hasFile('videos')) {
                foreach ($request->file('videos') as $video) {
                    $toy->addMedia($video)->toMediaCollection('videos');
                }
            }

            $toy->save();

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(ToyRequest $request, Toy $toy)
    {
        try {
            DB::beginTransaction();

            $toy->update($request->all());

            if ($request->hasFile('audios')) {
                $toy->clearMediaCollection('audios');
                foreach ($request->file('audios') as $audio) {
                    $toy->addMedia($audio)->toMediaCollection('audios');
                }
            }

            if ($request->hasFile('videos')) {
                $toy->clearMediaCollection('videos');
                foreach ($request->file('videos') as $video) {
                    $toy->addMedia($video)->toMediaCollection('videos');
                }
            }

            $toy->save();

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Toy $toy)
    {
        try {
            $toy->delete();
            $toy->clearMediaCollection('audios');
            $toy->clearMediaCollection('videos');
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

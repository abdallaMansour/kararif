<?php

namespace App\Http\Controllers\Rank;

use App\Models\Rank;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Rank\RankRequest;
use App\Http\Resources\Rank\DashboardRankResource;
use Illuminate\Support\Facades\DB;

class DashboardRankController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardRankResource::collection(Rank::orderBy('start_score')->get());
    }

    public function show(Rank $rank)
    {
        return new DashboardRankResource($rank);
    }

    public function create(RankRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['icon']);
            $rank = Rank::create($data);
            if ($request->hasFile('icon')) {
                $rank->addMediaFromRequest('icon')->toMediaCollection();
            }
            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(RankRequest $request, Rank $rank)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['icon']);
            $rank->update($data);
            if ($request->hasFile('icon')) {
                $rank->clearMediaCollection();
                $rank->addMediaFromRequest('icon')->toMediaCollection();
            }
            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Rank $rank)
    {
        try {
            $rank->clearMediaCollection();
            $rank->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Opinion;

use App\Models\Opinion;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Opinion\OpinionRequest;
use App\Http\Resources\Opinion\DashboardOpinionResource;
use Illuminate\Support\Facades\DB;

class DashboardOpinionController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardOpinionResource::collection(Opinion::all());
    }

    public function show(Opinion $opinion)
    {
        return new DashboardOpinionResource($opinion);
    }

    public function create(OpinionRequest $request)
    {
        try {
            DB::beginTransaction();
            $opinion = Opinion::create($request->all());

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(OpinionRequest $request, Opinion $opinion)
    {
        try {
            DB::beginTransaction();

            $opinion->update($request->all());

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Opinion $opinion)
    {
        try {
            $opinion->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

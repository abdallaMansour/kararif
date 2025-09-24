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
            Toy::create($request->all());

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
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

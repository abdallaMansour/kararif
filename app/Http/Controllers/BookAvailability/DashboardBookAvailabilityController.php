<?php

namespace App\Http\Controllers\BookAvailability;

use App\Models\BookAvailability;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\BookAvailability\BookAvailabilityRequest;
use App\Http\Resources\BookAvailability\DashboardBookAvailabilityResource;
use Illuminate\Support\Facades\DB;

class DashboardBookAvailabilityController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardBookAvailabilityResource::collection(BookAvailability::all());
    }

    public function show(BookAvailability $bookAvailability)
    {
        return new DashboardBookAvailabilityResource($bookAvailability);
    }

    public function create(BookAvailabilityRequest $request)
    {
        try {
            DB::beginTransaction();
            $bookAvailability = BookAvailability::create($request->all());

            if ($request->hasFile('image')) {
                $bookAvailability->addMediaFromRequest('image')->toMediaCollection();
            }

            $bookAvailability->save();

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(BookAvailabilityRequest $request, BookAvailability $bookAvailability)
    {
        try {
            DB::beginTransaction();

            $bookAvailability->update($request->all());

            if ($request->hasFile('image')) {
                $bookAvailability->clearMediaCollection();
                $bookAvailability->addMediaFromRequest('image')->toMediaCollection();
            }

            $bookAvailability->save();

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(BookAvailability $bookAvailability)
    {
        try {
            $bookAvailability->delete();
            $bookAvailability->clearMediaCollection();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

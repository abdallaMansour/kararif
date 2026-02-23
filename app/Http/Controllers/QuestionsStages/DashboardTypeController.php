<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Type;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\TypeRequest;
use App\Http\Resources\QuestionsStages\DashboardTypeResource;
use Illuminate\Support\Facades\DB;

class DashboardTypeController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardTypeResource::collection(Type::all());
    }

    public function show(Type $type)
    {
        return new DashboardTypeResource($type);
    }

    public function create(TypeRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['image']);
            $type = Type::create($data);

            if ($request->hasFile('image')) {
                $type->addMediaFromRequest('image')->toMediaCollection();
            }

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(TypeRequest $request, Type $type)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['image']);
            $type->update($data);

            if ($request->hasFile('image')) {
                $type->clearMediaCollection();
                $type->addMediaFromRequest('image')->toMediaCollection();
            }

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Type $type)
    {
        try {
            $type->clearMediaCollection();
            $type->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

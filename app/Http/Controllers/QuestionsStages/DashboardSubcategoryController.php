<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Subcategory;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\SubcategoryRequest;
use App\Http\Resources\QuestionsStages\DashboardSubcategoryResource;
use Illuminate\Support\Facades\DB;

class DashboardSubcategoryController extends Controller
{
    use ApiTrait;

    public function index()
    {
        $query = Subcategory::with('category')->withCount('questions');
        if (request()->has('category_id')) {
            $query->where('category_id', request('category_id'));
        }
        return DashboardSubcategoryResource::collection($query->get());
    }

    public function show(Subcategory $subcategory)
    {
        $subcategory->load('category', 'stage.questionGroups')->loadCount('questions');
        return new DashboardSubcategoryResource($subcategory);
    }

    public function create(SubcategoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['image']);
            if (empty($data['use_stage'])) {
                $data['stage_id'] = null;
            }
            $subcategory = Subcategory::create($data);

            if ($request->hasFile('image')) {
                $subcategory->addMediaFromRequest('image')->toMediaCollection();
            }

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(SubcategoryRequest $request, Subcategory $subcategory)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['image']);
            if (empty($data['use_stage'])) {
                $data['stage_id'] = null;
            }
            $subcategory->update($data);

            if ($request->hasFile('image')) {
                $subcategory->clearMediaCollection();
                $subcategory->addMediaFromRequest('image')->toMediaCollection();
            }

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Subcategory $subcategory)
    {
        try {
            $subcategory->clearMediaCollection();
            $subcategory->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

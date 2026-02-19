<?php

namespace App\Http\Controllers\QuestionsStages;

use App\Models\Category;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\QuestionsStages\CategoryRequest;
use App\Http\Resources\QuestionsStages\DashboardCategoryResource;
use Illuminate\Support\Facades\DB;

class DashboardCategoryController extends Controller
{
    use ApiTrait;

    public function index()
    {
        $stageId = request()->get('stage_id');
        $query = Category::query();
        if ($stageId) {
            $query->where('stage_id', $stageId);
        }
        return DashboardCategoryResource::collection($query->get());
    }

    public function show(Category $category)
    {
        return new DashboardCategoryResource($category);
    }

    public function create(CategoryRequest $request)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['image']);
            $category = Category::create($data);

            if ($request->hasFile('image')) {
                $category->addMediaFromRequest('image')->toMediaCollection();
            }

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(CategoryRequest $request, Category $category)
    {
        try {
            DB::beginTransaction();
            $data = $request->validated();
            unset($data['image']);
            $category->update($data);

            if ($request->hasFile('image')) {
                $category->clearMediaCollection();
                $category->addMediaFromRequest('image')->toMediaCollection();
            }

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Category $category)
    {
        try {
            $category->clearMediaCollection();
            $category->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

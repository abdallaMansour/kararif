<?php

namespace App\Http\Controllers\Adventurer;

use App\Models\Adventurer;
use App\Traits\ApiTrait;
use App\Http\Controllers\Controller;
use App\Http\Requests\Adventurer\AdventurerRequest;
use App\Http\Resources\Adventurer\DashboardAdventurerResource;
use Illuminate\Support\Facades\Hash;

class DashboardAdventurerController extends Controller
{
    use ApiTrait;

    public function index()
    {
        $query = Adventurer::query();
        if (request()->has('name')) {
            $query->where('name', 'like', '%' . request('name') . '%');
        }
        if (request()->has('email')) {
            $query->where('email', 'like', '%' . request('email') . '%');
        }
        if (request()->has('country')) {
            $query->where('country', 'like', '%' . request('country') . '%');
        }
        if (request()->has('score_min')) {
            $query->where('lifetime_score', '>=', request('score_min'));
        }
        if (request()->has('score_max')) {
            $query->where('lifetime_score', '<=', request('score_max'));
        }
        return DashboardAdventurerResource::collection($query->orderBy('lifetime_score', 'desc')->get());
    }

    public function show(Adventurer $adventurer)
    {
        return new DashboardAdventurerResource($adventurer);
    }

    public function create(AdventurerRequest $request)
    {
        try {
            $data = $request->validated();
            if (isset($data['pin_code'])) {
                $data['pin_code'] = Hash::make($data['pin_code']);
            }
            Adventurer::create($data);
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function update(AdventurerRequest $request, Adventurer $adventurer)
    {
        try {
            $data = $request->validated();
            if (isset($data['pin_code'])) {
                $data['pin_code'] = Hash::make($data['pin_code']);
            } else {
                unset($data['pin_code']);
            }
            $adventurer->update($data);
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }

    public function destroy(Adventurer $adventurer)
    {
        try {
            $adventurer->delete();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            return $this->sendError($th->getMessage(), [], 500);
        }
    }
}

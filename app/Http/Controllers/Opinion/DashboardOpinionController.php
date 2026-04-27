<?php

namespace App\Http\Controllers\Opinion;

use App\Models\Opinion;
use App\Traits\ApiTrait;
use App\Mail\OpinionMessageMail;
use App\Http\Controllers\Controller;
use App\Http\Requests\Opinion\OpinionRequest;
use App\Http\Resources\Opinion\DashboardOpinionResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DashboardOpinionController extends Controller
{
    use ApiTrait;

    public function index()
    {
        return DashboardOpinionResource::collection(Opinion::paginate());
    }

    public function show(Opinion $opinion)
    {
        return new DashboardOpinionResource($opinion);
    }

    public function create(OpinionRequest $request)
    {
        try {
            DB::beginTransaction();
            $opinion = Opinion::create($request->validated());
            $adminEmail = trim((string) (config('mail.contact_admin_email') ?: ''));
            if ($adminEmail !== '') {
                Mail::to($adminEmail)->send(new OpinionMessageMail($opinion));
            }

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

            $opinion->update($request->validated());

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

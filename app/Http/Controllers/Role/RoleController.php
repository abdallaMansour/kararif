<?php

namespace App\Http\Controllers\Role;

use App\Models\Role;
use App\Traits\ApiTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Resources\Role\RoleResource;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;

class RoleController extends Controller
{
    use ApiTrait;
    public function index()
    {
        $roles = Role::all();
        return response()->json(RoleResource::collection($roles));
    }

    /**
     * List roles for select/dropdown (id, name, display_name).
     */
    public function select()
    {
        $roles = Role::with('translations')->get();
        $data = $roles->map(function ($role) {
            $en = $role->translations->where('locale', 'en')->first();
            $ar = $role->translations->where('locale', 'ar')->first();
            return [
                'id' => $role->id,
                'name' => $role->name,
                'display_name_en' => $en?->display_name ?? $role->name,
                'display_name_ar' => $ar?->display_name ?? $role->name,
            ];
        });
        return response()->json(['data' => $data]);
    }

    public function show($roleId)
    {
        try {
            return  response()->json(new RoleResource(Role::findOrFail($roleId)));
        } catch (\Throwable $th) {
            return response()->json(['message' => 'Role not found'], 404);
        }
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            DB::beginTransaction();
            $role = Role::create($request->all());

            $role->translateOrNew('ar')->display_name = $request->display_name_ar;
            $role->translateOrNew('en')->display_name = $request->display_name_en;

            $role->save();

            $unique_permission = array_unique($request->permissions);

            for ($i = 0; $i < count($unique_permission); $i++) {
                DB::table('permission_role')->insert([
                    'permission_id' => $unique_permission[$i],
                    'role_id' => $role->id
                ]);
            }

            DB::commit();
            return $this->sendSuccess(__('response.created'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage());
        }
    }

    public function update(UpdateRoleRequest $request, $roleId)
    {
        try {
            DB::beginTransaction();
            $role = Role::find($roleId);

            if (!$role) {
                return response()->json(['message' => 'Role not found'], 404);
            }

            $role->translateOrNew('ar')->display_name = $request->display_name_ar;
            $role->translateOrNew('en')->display_name = $request->display_name_en;
            $role->save();

            $unique_permission = array_unique($request->permissions);

            DB::table('permission_role')->where('role_id', $roleId)->delete();

            for ($i = 0; $i < count($unique_permission); $i++) {
                DB::table('permission_role')->insert([
                    'permission_id' => $unique_permission[$i],
                    'role_id' => $role->id
                ]);
            }

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage());
        }
    }

    public function destroy($roleId)
    {
        try {
            DB::beginTransaction();
            $role = Role::find($roleId);

            if (!$role) {
                return  response()->json(['message' => 'Role not found'], 404);
            }

            $role->deleteTranslations();
            $role->delete();

            DB::commit();
            return $this->sendSuccess(__('response.deleted'));
        } catch (\Throwable $th) {
            DB::rollBack();
            return $this->sendError($th->getMessage());
        }
    }
}

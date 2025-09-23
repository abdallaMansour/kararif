<?php

namespace App\Http\Controllers\Role;

use App\Models\Role;
use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\Role\RoleResource;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Role\PermissionResource;
use App\Http\Requests\Role\AssignPermissionsRequest;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::all();
        return PermissionResource::collection($permissions);
    }

    // public function assign($roleId, AssignPermissionsRequest $request)
    // {
    //     try {
    //         $role = Role::findOrFail($roleId);

    //         $permissions = $request->input('permissions', []);

    //         $role->permissions()->detach();

    //         foreach ($permissions as $permissionId) {
    //             $role->permissions()->attach($permissionId);
    //         }
    //         return response()->json(['message' => "assigned successfully", 'data' => new RoleResource($role)]);
    //     } catch (\Throwable $th) {
    //         return response()->json([
    //             'status' => false,
    //             'error' => $th->getMessage()
    //         ], 500);
    //     }
    // }

    public function user_permission(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return  response()->json(['message' => $validator->errors()->first()], 422);
        }

        try {
            $user_id = $request->user_id;
            $role_id = $request->role_id;

            $user = User::find($user_id);
            $role = Role::find($role_id);

            if ($user->hasRole([$role->name])) {
                return response()->json(['message' => 'User already has this role'], 422);
            }
            $user->roles()->attach([$role_id]);

            return  response()->json(['message' => "User assigned successfully"]);
        } catch (\Throwable $th) {
            return  response()->json(['error' => $th->getMessage()], 500);
        }
    }
}

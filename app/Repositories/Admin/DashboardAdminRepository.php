<?php

namespace App\Repositories\Admin;

use Exception;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Admin\AdminResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class DashboardAdminRepository
{

    /**
     * @return LengthAwarePaginator
     */
    public function all()
    {
        return AdminResource::collection(User::all());
    }

    /**
     * @param array $data
     * @return Model
     */
    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            $admin = User::create($data);

            $admin->password = Hash::make($data['password']);

            $admin->roles()->attach($data['roles']);

            $admin->addMedia($data['image'])->toMediaCollection();

            $admin->save();

            DB::commit();
            return response()->json(['status' => true, 'message' => 'admin created successfully']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['status' => false, 'message' => $th->getMessage()], 500);
        }
    }

    /**
     * @param mixed $model
     * @return Model|void
     */
    public function find($model)
    {
        try {
            if ($model instanceof User) {
                return $model;
            }

            return User::findOrFail($model);
        } catch (\Throwable $th) {
            throw new HttpResponseException(response()->json(['error' => 'Not Found'], 404));
        }
    }

    /**
     * @param mixed $model
     * @param array $data
     * @return Model|User|void
     */
    public function update($admin_id, array $data)
    {
        try {
            DB::beginTransaction();

            $admin = $this->find($admin_id);

            $admin->update($data);

            $admin->password = Hash::make($data['password']);

            $admin->roles()->sync($data['roles']);

            $admin->save();

            if (isset($data['image'])) {
                $admin->clearMediaCollection();

                $admin->addMedia($data['image'])->toMediaCollection();
            }

            DB::commit();
            return response()->json(['status' => true, 'message' => 'admin updated successfully']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @param mixed $model
     * @throws Exception
     */
    public function delete($model)
    {
        $admin = $this->find($model);

        try {
            $admin->clearMediaCollection();
            $admin->delete();
            return response()->json(['status' => true, 'message' => 'admin deleted successfully']);
        } catch (\Throwable $th) {
            return  response()->json(['error' => $th->getMessage()], 500);
        }
    }
}

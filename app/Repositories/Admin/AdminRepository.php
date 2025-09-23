<?php

namespace App\Repositories\Admin;

use Exception;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\Admin\AdminResource;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AdminRepository
{

    /**
     * @return LengthAwarePaginator
     */
    public function all()
    {
        return new AdminResource(User::first());
    }

    /**
     * @param mixed $model
     * @param array $data
     * @return Model|User|void
     */
    public function update($contact_id, array $data)
    {
        try {
            DB::beginTransaction();

            $contact = User::first();
            $contact->update([
                'address:ar' => $data['address_ar'] ?? null,
                'address:en' => $data['address_en'] ?? null,
                'phone'      => $data['phone']      ?? null,
                'email'      => $data['email']      ?? null,
                'whatsapp'   => $data['whatsapp']   ?? null,
                'facebook'   => $data['facebook']   ?? null,
                'instagram'  => $data['instagram']  ?? null,
                'x'          => $data['x']          ?? null,
                'linkedin'   => $data['linkedin']   ?? null,
            ]);

            DB::commit();
            return response()->json(['status' => true, 'message' => 'contact updated successfully']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}

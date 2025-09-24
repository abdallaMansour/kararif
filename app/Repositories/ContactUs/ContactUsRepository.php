<?php

namespace App\Repositories\ContactUs;

use Exception;
use App\Models\ContactUs;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use App\Http\Resources\ContactUs\ContactUsResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ContactUsRepository
{

    public function all()
    {
        $contact_us = ContactUs::orderBy('is_read', 'ASC')->paginate();

        return ContactUsResource::collection($contact_us);
    }
    public function readAllContactUs()
    {
        ContactUs::where('is_read', false)->update(['is_read' => true]);

        return response()->json(['status' => true, 'message' => 'contact us read all successfully']);
    }

    public function create(array $data)
    {
        try {
            DB::beginTransaction();

            ContactUs::create([
                'name'    => $data['name'],
                'email'   => $data['email'],
                'message' => $data['message'],
            ]);
            DB::commit();

            return response()->json(['status' => true, 'message' => 'contact us created successfully']);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * @param mixed $model
     * @return Model|void
     */
    public function find($model)
    {
        try {
            if ($model instanceof ContactUs) {
                return $model;
            }

            return ContactUs::findOrFail($model);
        } catch (\Throwable $th) {
            throw new HttpResponseException(response()->json(['error' => 'Not Found'], 404));
        }
    }

    /**
     * @param mixed $model
     * @throws Exception
     */
    public function delete($model)
    {
        $contact_us = $this->find($model);
        try {
            $contact_us->delete();

            return  response()->json(['message' => 'contact us deleted successfully']);
        } catch (\Throwable $th) {
            return  response()->json(['error' => $th->getMessage()], 500);
        }
    }
}

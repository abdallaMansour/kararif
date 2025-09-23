<?php

namespace App\Http\Controllers\AboutUs;

use App\Models\AboutUs;
use App\Traits\ApiTrait;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\AboutUs\AboutUsRequest;
use App\Http\Resources\AboutUs\AboutUsResource;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Http\Resources\AboutUs\DashboardAboutUsResource;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class DashboardAboutUsController extends Controller
{
    use ApiTrait;

    /**
     * Show the specified resource.
     * 
     * @param AboutUs $hero
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function web_index()
    {
        return new AboutUsResource(AboutUs::first());
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new DashboardAboutUsResource(AboutUs::first());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AboutUsRequest $request)
    {
        try {
            DB::beginTransaction();
            $about_us = AboutUs::first();

            $about_us->update([
                'description_ar' => $request->description_ar,
                'description_en' => $request->description_en,
            ]);

            DB::commit();
            return $this->sendSuccess(__('response.updated'));
        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpResponseException(response()->json(['status' => false,  'message' => $th->getMessage()], 500));
        }
    }
}

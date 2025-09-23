<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\ApiResponse;
use App\Models\Setting;
use App\Traits\ApiTrait;
use App\Helpers\Languages;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;

class DashboardSettingController extends Controller
{
    use ApiTrait;

    public function index()
    {

        $settings = Setting::all();

        $data = [];

        foreach ($settings as $setting) {
            // لو فيه لغة نحطها ضمن المفتاح
            $key = $setting->lang ? "{$setting->key}_{$setting->lang}" : $setting->key;

            // لو المفتاح هو logo و value هو path نحوله لرابط كامل
            if ($setting->key === 'logo') {
                $data[$key] = $setting->getFirstMediaUrl();
            } else {
                $data[$key] = $setting->value;
            }
        }

        return response()->json([
            'data' => $data
        ]);


        // return DashboardSettingResource::collection(Setting::all());
    }

    /**
     * Show the specified resource.
     * 
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function update(UpdateSettingsRequest $request)
    {

        try {
            DB::beginTransaction();

            foreach (Setting::all() as $setting) {


                if ($setting->lang != null) {
                    foreach (Languages::LANGS as $lang) {
                        $setting->value = $request->{$setting->key . '_' . $lang};
                    }
                } elseif ($setting->key === 'logo') {
                    if ($request->hasFile('logo')) {
                        $setting->clearMediaCollection();

                        $setting->addMedia($request->file('logo'))->toMediaCollection();
                    }
                } else {
                    $setting->value = $request->{$setting->key};
                }

                $setting->save();
            }

            DB::commit();
            return ApiResponse::response(['status' => true, 'message' => __('response.updated')]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return ApiResponse::response(['error' => $th->getMessage()], 500);
        }
    }
}

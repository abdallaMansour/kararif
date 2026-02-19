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

            if ($setting->key === 'logo') {
                $data[$key] = $setting->getFirstMediaUrl();
            } elseif (in_array($setting->key, ['faqs', 'privacy_policy', 'terms_conditions'])) {
                $data[$key] = $setting->value ? json_decode($setting->value, true) : ['title' => null, 'content' => null];
            } elseif (in_array($setting->key, ['faqs_image', 'privacy_policy_image', 'terms_conditions_image'])) {
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
                if ($setting->lang != null && in_array($setting->key, ['faqs', 'privacy_policy', 'terms_conditions'])) {
                    $setting->value = json_encode([
                        'title' => $request->input($setting->key . '_title_' . $setting->lang),
                        'content' => $request->input($setting->key . '_content_' . $setting->lang),
                    ]);
                } elseif ($setting->lang != null) {
                    $setting->value = $request->{$setting->key . '_' . $setting->lang};
                } elseif ($setting->key === 'logo') {
                    if ($request->hasFile('logo')) {
                        $setting->clearMediaCollection();
                        $setting->addMedia($request->file('logo'))->toMediaCollection();
                    }
                } elseif (in_array($setting->key, ['faqs_image', 'privacy_policy_image', 'terms_conditions_image'])) {
                    if ($request->hasFile($setting->key)) {
                        $setting->clearMediaCollection();
                        $setting->addMedia($request->file($setting->key))->toMediaCollection();
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

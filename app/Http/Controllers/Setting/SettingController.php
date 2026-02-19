<?php

namespace App\Http\Controllers\Setting;

use App\Models\Setting;
use App\Http\Controllers\Controller;
use App\Http\Resources\Setting\SettingResource;
use App\Http\Requests\Settings\UpdateSettingsRequest;

class SettingController extends Controller
{
    /**
     * Show the specified resource.
     * 
     * @return \Illuminate\Http\JsonResponse
     * @throws AuthorizationException
     */
    public function index()
    {

        $settings = Setting::where('lang', app()->getLocale())
            ->orWhereNull('lang')
            ->get();

        $data = [];

        foreach ($settings as $setting) {
            $key = $setting->key;

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
    }


    public function logo()
    {
        return response()->json(['logo' => Setting::where('key', 'logo')->first()->getFirstMediaUrl()]);
    }
}

<?php

namespace App\Http\Controllers\Setting;

use App\Helpers\ApiResponse;
use App\Models\Setting;
use App\Traits\ApiTrait;
use App\Helpers\Languages;
use App\Helpers\SettingHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Http\Requests\Settings\UpdateDashboardTermsRequest;
use App\Http\Requests\Settings\UpdateDashboardPrivacyRequest;
use App\Http\Requests\Settings\UpdateDashboardFaqRequest;

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
                $decoded = $setting->value ? json_decode($setting->value, true) : null;
                $data[$key] = in_array($setting->key, ['privacy_policy', 'terms_conditions'])
                    ? SettingHelper::normalizeStepsFormat(is_array($decoded) ? $decoded : null)
                    : ($decoded ?? ['title' => null, 'content' => null]);
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
                    if (in_array($setting->key, ['privacy_policy', 'terms_conditions'])) {
                        $steps = $request->input($setting->key . '_steps_' . $setting->lang);
                        $lastUpdated = $request->input($setting->key . '_last_updated_' . $setting->lang);
                        if (is_array($steps)) {
                            $setting->value = json_encode([
                                'last_updated' => $lastUpdated ?: null,
                                'steps' => array_values(array_map(fn ($s) => [
                                    'title' => $s['title'] ?? null,
                                    'content' => $s['content'] ?? null,
                                ], $steps)),
                            ]);
                        } else {
                            $setting->value = json_encode([
                                'last_updated' => null,
                                'steps' => [
                                    [
                                        'title' => $request->input($setting->key . '_title_' . $setting->lang),
                                        'content' => $request->input($setting->key . '_content_' . $setting->lang),
                                    ],
                                ],
                            ]);
                        }
                    } else {
                        $setting->value = json_encode([
                            'title' => $request->input($setting->key . '_title_' . $setting->lang),
                            'content' => $request->input($setting->key . '_content_' . $setting->lang),
                        ]);
                    }
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

    /**
     * Dashboard: show terms and conditions only (all languages + image).
     */
    public function showTerms()
    {
        $data = $this->dashboardSettingDataFor('terms_conditions');
        return response()->json(['data' => $data]);
    }

    /**
     * Dashboard: update terms and conditions only.
     */
    public function updateTerms(UpdateDashboardTermsRequest $request)
    {
        return $this->updateSettingsByKey($request, 'terms_conditions', true);
    }

    /**
     * Dashboard: show privacy policy only (all languages + image).
     */
    public function showPrivacy()
    {
        $data = $this->dashboardSettingDataFor('privacy_policy');
        return response()->json(['data' => $data]);
    }

    /**
     * Dashboard: update privacy policy only.
     */
    public function updatePrivacy(UpdateDashboardPrivacyRequest $request)
    {
        return $this->updateSettingsByKey($request, 'privacy_policy', true);
    }

    /**
     * Dashboard: show FAQ only (all languages + image).
     */
    public function showFaq()
    {
        $data = $this->dashboardSettingDataFor('faqs');
        return response()->json(['data' => $data]);
    }

    /**
     * Dashboard: update FAQ only.
     */
    public function updateFaq(UpdateDashboardFaqRequest $request)
    {
        return $this->updateSettingsByKey($request, 'faqs', false);
    }

    /**
     * Build data for dashboard show (terms, privacy, or faq) from settings.
     */
    private function dashboardSettingDataFor(string $key): array
    {
        $settings = Setting::where('key', $key)->orWhere('key', $key . '_image')->get();
        $data = [];
        foreach ($settings as $setting) {
            if ($setting->key === $key . '_image') {
                $data[$key . '_image'] = $setting->getFirstMediaUrl();
                continue;
            }
            $langKey = $setting->lang ? "{$key}_{$setting->lang}" : $key;
            $decoded = $setting->value ? json_decode($setting->value, true) : null;
            $data[$langKey] = in_array($key, ['privacy_policy', 'terms_conditions'])
                ? SettingHelper::normalizeStepsFormat(is_array($decoded) ? $decoded : null)
                : ($decoded ?? ['title' => null, 'content' => null]);
        }
        return $data;
    }

    /**
     * Update only settings for the given key (terms_conditions, privacy_policy, or faqs).
     *
     * @param  bool  $stepsFormat  true for terms/privacy (steps + last_updated), false for faq (title + content)
     */
    private function updateSettingsByKey(\Illuminate\Foundation\Http\FormRequest $request, string $key, bool $stepsFormat)
    {
        try {
            DB::beginTransaction();
            $settings = Setting::where('key', $key)->orWhere('key', $key . '_image')->get();
            foreach ($settings as $setting) {
                if ($setting->key === $key . '_image') {
                    if ($request->hasFile($key . '_image')) {
                        $setting->clearMediaCollection();
                        $setting->addMedia($request->file($key . '_image'))->toMediaCollection();
                    }
                    $setting->save();
                    continue;
                }
                if ($setting->lang === null) {
                    continue;
                }
                $lang = $setting->lang;
                if ($stepsFormat) {
                    $steps = $request->input($key . '_steps_' . $lang);
                    $lastUpdated = $request->input($key . '_last_updated_' . $lang);
                    if (is_array($steps)) {
                        $setting->value = json_encode([
                            'last_updated' => $lastUpdated ?: null,
                            'steps' => array_values(array_map(fn ($s) => [
                                'title' => $s['title'] ?? null,
                                'content' => $s['content'] ?? null,
                            ], $steps)),
                        ]);
                    } else {
                        $setting->value = json_encode([
                            'last_updated' => null,
                            'steps' => [
                                [
                                    'title' => $request->input($key . '_title_' . $lang),
                                    'content' => $request->input($key . '_content_' . $lang),
                                ],
                            ],
                        ]);
                    }
                } else {
                    $setting->value = json_encode([
                        'title' => $request->input($key . '_title_' . $lang),
                        'content' => $request->input($key . '_content_' . $lang),
                    ]);
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

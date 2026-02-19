<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds only the new settings: FAQs, Privacy Policy, Terms (per locale) and their image keys.
 * Does not touch existing settings. Run with: php artisan db:seed --class=SeedSettingsFaqsPrivacyTermsOnlySeeder
 */
class SeedSettingsFaqsPrivacyTermsOnlySeeder extends Seeder
{
    public function run(): void
    {
        $sections = ['faqs', 'privacy_policy', 'terms_conditions'];
        $locales = ['ar', 'en'];

        foreach ($sections as $section) {
            foreach ($locales as $lang) {
                Setting::firstOrCreate(
                    [
                        'key' => $section,
                        'lang' => $lang,
                    ],
                    [
                        'value' => json_encode(['title' => null, 'content' => null]),
                    ]
                );
            }
        }

        foreach (['faqs_image', 'privacy_policy_image', 'terms_conditions_image'] as $key) {
            Setting::firstOrCreate(
                ['key' => $key, 'lang' => null],
                ['value' => null]
            );
        }
    }
}

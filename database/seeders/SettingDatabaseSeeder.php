<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;

class SettingDatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::insert($this->articles());
        Setting::insert($this->faqsPrivacyTermsArticles());

        Setting::create([
            'key' => 'logo',
        ])->addMedia(__DIR__ . '/setting_img/logo.svg')->preservingOriginal()->toMediaCollection();

        Setting::create(['key' => 'faqs_image', 'value' => null]);
        Setting::create(['key' => 'privacy_policy_image', 'value' => null]);
        Setting::create(['key' => 'terms_conditions_image', 'value' => null]);
    }

    /**
     * FAQs, Privacy Policy, Terms and Conditions (per locale, value as JSON).
     */
    public function faqsPrivacyTermsArticles(): array
    {
        $sections = ['faqs', 'privacy_policy', 'terms_conditions'];
        $locales = ['ar', 'en'];
        $rows = [];
        foreach ($sections as $section) {
            foreach ($locales as $lang) {
                $rows[] = [
                    'key' => $section,
                    'value' => json_encode(['title' => null, 'content' => null]),
                    'lang' => $lang,
                ];
            }
        }
        return $rows;
    }

    public function articles(): array
    {
        return [
            [
                'key' => 'footer_description',
                'value' => 'الوصف بالعربية',
                'lang' => 'ar',
            ],
            [
                'key' => 'footer_description',
                'value' => 'footer description in English',
                'lang' => 'en',
            ],
            [
                'key' => 'address',
                'value' => 'العنوان بالعربية',
                'lang' => 'ar',
            ],
            [
                'key' => 'address',
                'value' => 'Address in English',
                'lang' => 'en',
            ],
            [
                'key' => 'phone',
                'value' => '+01019113472',
                'lang' => null,
            ],
            [
                'key' => 'email',
                'value' => 'abdalla.mansour.dev@gmail.com',
                'lang' => null,
            ],
            [
                'key' => 'whatsapp',
                'value' => 'https://wa.me/201019113472',
                'lang' => null,
            ],
            [
                'key' => 'facebook',
                'value' => 'https://facebook.com',
                'lang' => null,
            ],
            [
                'key' => 'instagram',
                'value' => 'https://instegram.com',
                'lang' => null,
            ],
            [
                'key' => 'x',
                'value' => 'https://x.com',
                'lang' => null,
            ],
            [
                'key' => 'linkedin',
                'value' => 'https://linkedin.com',
                'lang' => null,
            ],
        ];
    }
}

<?php

namespace Database\Seeders;

use App\Models\Seo;
use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;

class SeoDatabaseSeeder extends Seeder
{

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->seed($this->articles());
    }

    /**
     * Run the database seeds.
     *
     * @param array $articles
     * @return void
     */
    public function seed(array $articles = []): void
    {
        foreach ($articles as $lab) {
            $data = Arr::except($lab, ['images']);

            $seo = Seo::create($data);

            if (isset($lab['images'])) {
                foreach ($lab['images'] as $key => $img) {
                    $seo
                        ->addMedia(__DIR__ . $img)
                        ->preservingOriginal()
                        ->toMediaCollection($key);
                }
            }
        }
    }

    public function articles(): array
    {
        return [
            [
                'title:ar' => 'الصفحه الرئيسيه',
                'description:ar' => 'وصف الصفحه',
                'site_name:ar' => 'إسم الموقع',
                'keyword:ar' => 'الكلمات المفتاحيه',

                'title:en' => 'home page',
                'description:en' => 'description',
                'site_name:en' => 'site name',
                'keyword:en' => 'keyword',

                'name_id' => 'home',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                'title:ar' => 'الصفحه عنا',
                'description:ar' => 'وصف الصفحه',
                'site_name:ar' => 'إسم الموقع',
                'keyword:ar' => 'الكلمات المفتاحيه',

                'title:en' => 'about us page',
                'description:en' => 'description',
                'site_name:en' => 'site name',
                'keyword:en' => 'keyword',

                'name_id' => 'about_us',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                'title:ar' => 'الصفحه إتصل بنا',
                'description:ar' => 'وصف الصفحه',
                'site_name:ar' => 'إسم الموقع',
                'keyword:ar' => 'الكلمات المفتاحيه',

                'title:en' => 'contact us page',
                'description:en' => 'description',
                'site_name:en' => 'site name',
                'keyword:en' => 'keyword',

                'name_id' => 'contact_us',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
        ];
    }
}

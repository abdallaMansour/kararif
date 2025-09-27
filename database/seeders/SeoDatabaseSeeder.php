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

        // ids home, author, opinion, story, availability, about_us, contact_us, electronic_games, coloring

        /*
            الرئيسية 
            المؤلف 
            رأيك بخراريف (اراء العملاء)
            اقرأ خراريف (قصص القراءة)
            اماكن توفرها
            ماهي خراريف (من نحن)
            تواصل معنا 
            العاب الكترونيه
            تلوين
        */
        return [
            [
                // 'title:ar' => 'الصفحه الرئيسيه',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'home page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'home page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'home',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه المؤلف',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'author page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'author page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'author',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه رأيك بخراريف (اراء العملاء)',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'opinion page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'opinion page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'opinion',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه اقرأ خراريف (قصص القراءة)',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'story page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'story page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'story',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه اماكن توفرها',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'availability page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'availability page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'availability',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه ماهي خراريف (من نحن)',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'about us page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'about us page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'about_us',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            // [
            //     'title:ar' => 'الصفحه عنا',
            //     'description:ar' => 'وصف الصفحه',
            //     'site_name:ar' => 'إسم الموقع',
            //     'keyword:ar' => 'الكلمات المفتاحيه',

            //     'title:en' => 'about us page',
            //     'description:en' => 'description',
            //     'site_name:en' => 'site name',
            //     'keyword:en' => 'keyword',

            //     'name_id' => 'about_us',
            //     'images' => [
            //         'image' => '/seo_imgs/project.png',
            //         'icon' => '/seo_imgs/user_icon.png',
            //     ]
            // ],
            [
                // 'title:ar' => 'الصفحه إتصل بنا',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'contact us page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'contact us page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'contact_us',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه العاب الكترونيه',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'electronic games page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'electronic games page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'electronic_games',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
            [
                // 'title:ar' => 'الصفحه تلوين',
                // 'description:ar' => 'وصف الصفحه',
                // 'site_name:ar' => 'إسم الموقع',
                // 'keyword:ar' => 'الكلمات المفتاحيه',

                // 'title:en' => 'coloring page',
                // 'description:en' => 'description',
                // 'site_name:en' => 'site name',
                // 'keyword:en' => 'keyword',

                'title' => 'coloring page',
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',

                'name_id' => 'coloring',
                'images' => [
                    'image' => '/seo_imgs/project.png',
                    'icon' => '/seo_imgs/user_icon.png',
                ]
            ],
        ];
    }
}

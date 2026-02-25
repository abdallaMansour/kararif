<?php

namespace Database\Seeders;

use App\Models\FaqItem;
use App\Models\News;
use App\Models\PaymentPackage;
use Illuminate\Database\Seeder;

class FrontendDataSeeder extends Seeder
{
    public function run(): void
    {
        FaqItem::insert([
            ['question' => 'كيف ألعب تتابع الفيديو؟', 'answer' => 'اختر نوع الأسئلة والفئة ثم ادخل الرمز أو أنشئ غرفة وادعُ أصدقاءك.', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['question' => 'كيف أعبئ رصيدي؟', 'answer' => 'يمكنك شراء الرصيد من صفحة تعبئة الرصيد.', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        News::insert([
            ['title' => 'مرحباً بتتابع الفيديو', 'summary' => 'انطلاق لعبة تتابع الفيديو.', 'body' => null, 'thumbnail' => null, 'url' => null, 'published_at' => now(), 'created_at' => now(), 'updated_at' => now()],
        ]);

        PaymentPackage::insert([
            ['name' => '100 لعبة', 'points' => 100, 'price' => 9.99, 'currency' => 'SAR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => '500 لعبة', 'points' => 500, 'price' => 39.99, 'currency' => 'SAR', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_products', function (Blueprint $table) {
            $table->id();
            $table->string('sku')->unique();
            $table->string('name_ar');
            $table->decimal('price_aed', 10, 2);
            $table->string('image_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_sellable')->default(true);
            $table->timestamps();
        });

        DB::table('shop_products')->insert([
            [
                'sku' => 'book',
                'name_ar' => 'الكتاب',
                'price_aed' => 70,
                'image_url' => '/images/shop/book.png',
                'is_active' => true,
                'is_sellable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sku' => 'uno-kharareef',
                'name_ar' => 'أونو خراريف',
                'price_aed' => 30,
                'image_url' => '/images/shop/uno-kharareef.png',
                'is_active' => true,
                'is_sellable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'sku' => 'stickers',
                'name_ar' => 'الملصقات',
                'price_aed' => 10,
                'image_url' => '/images/shop/stickers.png',
                'is_active' => true,
                'is_sellable' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_products');
    }
};

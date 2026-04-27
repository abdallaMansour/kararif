<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('seo')) {
            return;
        }

        $pages = [
            'shop_products' => 'Shop products page',
            'shop_checkout' => 'Shop checkout page',
            'shop_order_confirmation' => 'Shop order confirmation page',
        ];

        foreach ($pages as $nameId => $title) {
            $exists = DB::table('seo')->where('name_id', $nameId)->exists();
            if ($exists) {
                continue;
            }

            DB::table('seo')->insert([
                'title' => $title,
                'description' => 'description',
                'site_name' => 'site name',
                'keyword' => 'keyword',
                'name_id' => $nameId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('seo')) {
            return;
        }

        DB::table('seo')
            ->whereIn('name_id', ['shop_products', 'shop_checkout', 'shop_order_confirmation'])
            ->delete();
    }
};

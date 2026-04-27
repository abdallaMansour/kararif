<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $pages = [
            'shop_products' => 'Shop products page',
            'shop_checkout' => 'Shop checkout page',
            'shop_order_confirmation' => 'Shop order confirmation page',
        ];

        foreach ($pages as $nameId => $title) {
            $exists = DB::table('seos')->where('name_id', $nameId)->exists();
            if ($exists) {
                continue;
            }

            DB::table('seos')->insert([
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
        DB::table('seos')
            ->whereIn('name_id', ['shop_products', 'shop_checkout', 'shop_order_confirmation'])
            ->delete();
    }
};

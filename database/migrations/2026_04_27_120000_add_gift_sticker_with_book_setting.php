<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $exists = DB::table('settings')
            ->where('key', 'gift_sticker_with_book')
            ->whereNull('lang')
            ->exists();

        if (! $exists) {
            DB::table('settings')->insert([
                'key' => 'gift_sticker_with_book',
                'value' => '0',
                'lang' => null,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')
            ->where('key', 'gift_sticker_with_book')
            ->whereNull('lang')
            ->delete();
    }
};

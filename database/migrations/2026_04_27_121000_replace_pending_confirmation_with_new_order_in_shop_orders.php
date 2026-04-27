<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shop_orders')
            ->where('status', 'pending_confirmation')
            ->update(['status' => 'new_order']);
    }

    public function down(): void
    {
        DB::table('shop_orders')
            ->where('status', 'new_order')
            ->whereNotNull('paid_at')
            ->update(['status' => 'pending_confirmation']);
    }
};

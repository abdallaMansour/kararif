<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_packages', function (Blueprint $table) {
            $table->unsignedInteger('sessions_count')->default(0)->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('payment_packages', function (Blueprint $table) {
            $table->dropColumn('sessions_count');
        });
    }
};

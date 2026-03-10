<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->string('prize_label_ar')->nullable()->after('prize_value');
        });
    }

    public function down(): void
    {
        Schema::table('ranks', function (Blueprint $table) {
            $table->dropColumn('prize_label_ar');
        });
    }
};

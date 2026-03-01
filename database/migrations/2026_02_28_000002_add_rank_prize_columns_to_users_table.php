<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedTinyInteger('rank_discount_percent')->nullable()->after('available_sessions');
            $table->unsignedTinyInteger('rank_discount_uses_left')->nullable()->after('rank_discount_percent');
            $table->json('granted_discount_rank_ids')->nullable()->after('rank_discount_uses_left');
            $table->json('granted_session_rank_ids')->nullable()->after('granted_discount_rank_ids');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'rank_discount_percent',
                'rank_discount_uses_left',
                'granted_discount_rank_ids',
                'granted_session_rank_ids',
            ]);
        });
    }
};

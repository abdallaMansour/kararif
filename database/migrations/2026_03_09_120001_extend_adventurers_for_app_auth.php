<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventurers', function (Blueprint $table) {
            $table->string('password')->nullable()->after('pin_code');
            $table->string('username')->unique()->nullable()->after('name');
            $table->string('phone')->unique()->nullable()->after('email');
            $table->string('remember_token')->nullable()->after('password');
            $table->unsignedInteger('points')->default(0)->after('number_surrender_times');
            $table->unsignedInteger('surrender_count')->default(0)->after('points');
            $table->string('country_label')->nullable()->after('country');
            $table->string('country_code', 10)->nullable()->after('country_label');
            $table->foreignId('avatar_id')->nullable()->after('country_code')->constrained('avatars')->nullOnDelete();
            $table->unsignedInteger('available_sessions')->default(2)->after('avatar_id');
            $table->unsignedTinyInteger('rank_discount_percent')->default(0)->nullable()->after('available_sessions');
            $table->unsignedInteger('rank_discount_uses_left')->default(0)->nullable()->after('rank_discount_percent');
            $table->json('granted_discount_rank_ids')->nullable()->after('rank_discount_uses_left');
            $table->json('granted_session_rank_ids')->nullable()->after('granted_discount_rank_ids');
        });
    }

    public function down(): void
    {
        Schema::table('adventurers', function (Blueprint $table) {
            $table->dropForeign(['avatar_id']);
            $table->dropColumn([
                'password',
                'username',
                'phone',
                'remember_token',
                'points',
                'surrender_count',
                'country_label',
                'country_code',
                'avatar_id',
                'available_sessions',
                'rank_discount_percent',
                'rank_discount_uses_left',
                'granted_discount_rank_ids',
                'granted_session_rank_ids',
            ]);
        });
    }
};

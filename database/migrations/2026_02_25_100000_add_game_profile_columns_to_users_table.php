<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable()->after('name');
            $table->unsignedInteger('balance')->default(0)->after('remember_token');
            $table->unsignedSmallInteger('level')->default(1)->after('balance');
            $table->unsignedInteger('points')->default(0)->after('level');
            $table->string('avatar')->nullable()->after('points');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'balance', 'level', 'points', 'avatar']);
        });
    }
};

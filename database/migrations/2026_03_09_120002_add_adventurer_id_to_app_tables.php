<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->foreignId('adventurer_id')->nullable()->after('user_id')->constrained('adventurers')->nullOnDelete();
        });

        Schema::table('rooms', function (Blueprint $table) {
            $table->foreignId('created_by_adventurer_id')->nullable()->after('created_by')->constrained('adventurers')->nullOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('adventurer_id')->nullable()->after('user_id')->constrained('adventurers')->nullOnDelete();
        });

        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->foreignId('adventurer_id')->nullable()->after('user_id')->constrained('adventurers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('room_players', function (Blueprint $table) {
            $table->dropForeign(['adventurer_id']);
        });
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropForeign(['created_by_adventurer_id']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['adventurer_id']);
        });
        Schema::table('coupon_usages', function (Blueprint $table) {
            $table->dropForeign(['adventurer_id']);
        });
    }
};

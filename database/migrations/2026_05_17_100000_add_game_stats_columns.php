<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adventurers', function (Blueprint $table) {
            $table->unsignedInteger('number_game_losses')->default(0)->after('number_full_winnings');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->timestamp('stats_applied_at')->nullable()->after('winner_team_ids');
        });
    }

    public function down(): void
    {
        Schema::table('adventurers', function (Blueprint $table) {
            $table->dropColumn('number_game_losses');
        });

        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn('stats_applied_at');
        });
    }
};

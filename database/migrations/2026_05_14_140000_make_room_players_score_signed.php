<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Life-points wrong answers apply −10 to team score on the leader; UNSIGNED cannot store negatives.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE `room_players` MODIFY `score` BIGINT NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('UPDATE `room_players` SET `score` = 0 WHERE `score` < 0');
        DB::statement('ALTER TABLE `room_players` MODIFY `score` INT UNSIGNED NOT NULL DEFAULT 0');
    }
};

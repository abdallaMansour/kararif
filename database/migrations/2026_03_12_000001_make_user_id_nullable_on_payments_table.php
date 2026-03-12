<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Make user_id nullable so Adventurers (who have adventurer_id, not user_id) can create payments
        DB::statement('ALTER TABLE payments MODIFY user_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE payments MODIFY user_id BIGINT UNSIGNED NOT NULL');
    }
};

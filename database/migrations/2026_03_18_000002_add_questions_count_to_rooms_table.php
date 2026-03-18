<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            // Total questions to play in this session (split across `rounds`).
            // Nullable for backward compatibility with legacy rooms.
            $table->unsignedTinyInteger('questions_count')->nullable()->after('rounds');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('questions_count');
        });
    }
};


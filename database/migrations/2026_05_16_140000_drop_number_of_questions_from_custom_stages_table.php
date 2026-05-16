<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_stages', function (Blueprint $table) {
            $table->dropColumn('number_of_questions');
        });
    }

    public function down(): void
    {
        Schema::table('custom_stages', function (Blueprint $table) {
            $table->unsignedInteger('number_of_questions')->nullable()->after('life_points_per_question');
        });
    }
};

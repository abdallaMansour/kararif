<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_answers', function (Blueprint $table) {
            $table->unsignedSmallInteger('question_round')->nullable()->after('custom_question_id');
            $table->index(['game_session_id', 'question_round']);
        });
    }

    public function down(): void
    {
        Schema::table('session_answers', function (Blueprint $table) {
            $table->dropIndex(['game_session_id', 'question_round']);
            $table->dropColumn('question_round');
        });
    }
};

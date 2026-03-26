<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_answers', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->foreignId('custom_question_id')->nullable()->after('question_id')->constrained('custom_questions')->nullOnDelete();
            $table->foreignId('question_id')->nullable()->change();
            $table->foreign('question_id')->references('id')->on('questions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('session_answers', function (Blueprint $table) {
            $table->dropForeign(['custom_question_id']);
            $table->dropConstrainedForeignId('custom_question_id');
            $table->dropForeign(['question_id']);
            $table->foreign('question_id')->references('id')->on('questions')->cascadeOnDelete();
        });
    }
};

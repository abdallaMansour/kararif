<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->string('stage_type')->nullable()->after('name'); // 'questions_group' | 'life_points'
            $table->unsignedInteger('question_groups_count')->nullable()->after('stage_type');
            $table->decimal('life_points_per_question', 10, 2)->nullable()->after('number_of_questions');
        });
    }

    public function down(): void
    {
        Schema::table('stages', function (Blueprint $table) {
            $table->dropColumn(['stage_type', 'question_groups_count', 'life_points_per_question']);
        });
    }
};

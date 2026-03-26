<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('owner_adventurer_id')->nullable()->constrained('adventurers')->nullOnDelete();
            $table->foreignId('custom_category_id')->nullable()->constrained('custom_categories')->nullOnDelete();
            $table->string('name');
            $table->string('question_kind')->default('normal');
            $table->text('answer_1');
            $table->boolean('is_correct_1')->default(false);
            $table->text('answer_2');
            $table->boolean('is_correct_2')->default(false);
            $table->text('answer_3');
            $table->boolean('is_correct_3')->default(false);
            $table->text('answer_4');
            $table->boolean('is_correct_4')->default(false);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->index(['owner_user_id', 'owner_adventurer_id']);
            $table->index('custom_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_questions');
    }
};

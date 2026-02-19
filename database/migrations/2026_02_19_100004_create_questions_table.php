<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained('subcategories')->onDelete('cascade');
            $table->foreignId('type_id')->constrained('types')->onDelete('cascade');
            $table->string('name');
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

            $table->index(['stage_id', 'category_id', 'subcategory_id', 'type_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

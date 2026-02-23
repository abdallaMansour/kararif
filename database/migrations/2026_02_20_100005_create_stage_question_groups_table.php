<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stage_question_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stage_id')->constrained('stages')->onDelete('cascade');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index('stage_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stage_question_groups');
    }
};

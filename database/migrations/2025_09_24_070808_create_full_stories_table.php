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
        Schema::create('full_stories', function (Blueprint $table) {
            $table->id();

            $table->string('title');
            $table->text('description');
            $table->tinyInteger('type'); // 1 or 2 or 3
            $table->text('link')->nullable();
            $table->boolean('is_free')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('full_stories');
    }
};

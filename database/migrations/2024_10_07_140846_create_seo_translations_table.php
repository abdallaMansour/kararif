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
        Schema::create('seo_translations', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('seo_id')->constrained('seo', 'id')->cascadeOnDelete();

            $table->string('title');
            $table->text('description');
            $table->string('site_name')->nullable();
            $table->text('keyword');

            $table->string('locale')->index();
            $table->unique(['seo_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_translations');
    }
};

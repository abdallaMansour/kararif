<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
            $table->dropIndex(['stage_id', 'category_id', 'subcategory_id', 'type_id']);
            $table->dropColumn('stage_id');
            $table->index(['type_id', 'category_id', 'subcategory_id']);
        });
    }

    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->constrained('stages')->onDelete('cascade');
            $table->dropIndex(['type_id', 'category_id', 'subcategory_id']);
            $table->index(['stage_id', 'category_id', 'subcategory_id', 'type_id']);
        });
    }
};

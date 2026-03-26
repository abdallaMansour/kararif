<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->boolean('is_custom')->default(false)->after('code');
            $table->foreignId('custom_category_id')->nullable()->after('subcategory_id')->constrained('custom_categories')->nullOnDelete();
            $table->unsignedSmallInteger('life_points')->nullable()->after('questions_count');
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropConstrainedForeignId('custom_category_id');
            $table->dropColumn(['is_custom', 'life_points']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
            $table->dropIndex(['stage_id', 'category_id']);
            $table->dropColumn('stage_id');
            $table->dropColumn(['monthly_price', 'yearly_price']);
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->constrained('stages')->onDelete('cascade');
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->index(['stage_id', 'category_id']);
        });
    }
};

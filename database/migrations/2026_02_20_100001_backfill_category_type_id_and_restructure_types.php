<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill: set category.type_id from first type that has category_id = category.id
        DB::statement('
            UPDATE categories
            SET type_id = (SELECT id FROM types WHERE types.category_id = categories.id LIMIT 1)
            WHERE type_id IS NULL AND EXISTS (SELECT 1 FROM types WHERE types.category_id = categories.id)
        ');

        // Ensure every category has a type: use first existing type as default
        $defaultTypeId = DB::table('types')->value('id');
        if ($defaultTypeId) {
            DB::table('categories')->whereNull('type_id')->update(['type_id' => $defaultTypeId]);
        }

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
            $table->dropColumn('stage_id');
            $table->dropColumn(['monthly_price', 'yearly_price']);
        });

        Schema::table('types', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
            $table->dropForeign(['category_id']);
            $table->dropForeign(['subcategory_id']);
            $table->dropIndex(['stage_id', 'category_id', 'subcategory_id']);
            $table->dropColumn(['stage_id', 'category_id', 'subcategory_id', 'monthly_price', 'yearly_price']);
        });
    }

    public function down(): void
    {
        Schema::table('types', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->constrained('stages')->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->foreignId('subcategory_id')->nullable()->constrained('subcategories')->onDelete('cascade');
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();
            $table->index(['stage_id', 'category_id', 'subcategory_id']);
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('stage_id')->nullable()->constrained('stages')->onDelete('cascade');
            $table->decimal('monthly_price', 10, 2)->nullable();
            $table->decimal('yearly_price', 10, 2)->nullable();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->boolean('use_stage')->default(false)->after('status');
            $table->foreignId('stage_id')->nullable()->after('use_stage')->constrained('stages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('subcategories', function (Blueprint $table) {
            $table->dropForeign(['stage_id']);
            $table->dropColumn(['use_stage', 'stage_id']);
        });
    }
};

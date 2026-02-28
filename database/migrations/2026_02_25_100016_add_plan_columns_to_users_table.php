<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('surrender_count')->default(0)->after('avatar');
            $table->string('country_label')->nullable()->after('surrender_count');
            $table->string('country_code', 10)->nullable()->after('country_label');
            $table->foreignId('avatar_id')->nullable()->after('country_code')->constrained('avatars')->nullOnDelete();
            $table->unsignedInteger('available_sessions')->default(2)->after('avatar_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['avatar_id']);
            $table->dropColumn([
                'surrender_count',
                'country_label',
                'country_code',
                'avatar_id',
                'available_sessions',
            ]);
        });
    }
};

<?php

use App\Models\StageQuestionGroup;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('media')) {
            return;
        }

        DB::table('media')
            ->where('model_type', StageQuestionGroup::class)
            ->where('collection_name', 'start_video')
            ->update(['collection_name' => 'lunch_video']);
    }

    public function down(): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('media')) {
            return;
        }

        DB::table('media')
            ->where('model_type', StageQuestionGroup::class)
            ->where('collection_name', 'lunch_video')
            ->update(['collection_name' => 'start_video']);
    }
};

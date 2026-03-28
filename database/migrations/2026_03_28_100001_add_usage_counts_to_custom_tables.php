<?php

use App\Models\GameSession;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_categories', function (Blueprint $table) {
            $table->unsignedInteger('usage_count')->default(0)->after('status');
        });

        Schema::table('custom_questions', function (Blueprint $table) {
            $table->unsignedInteger('usage_count')->default(0)->after('status');
        });

        $this->backfillCategoryUsage();
        $this->backfillQuestionUsage();
    }

    private function backfillCategoryUsage(): void
    {
        $rows = DB::table('game_sessions')
            ->join('rooms', 'rooms.id', '=', 'game_sessions.room_id')
            ->where('rooms.is_custom', true)
            ->whereNotNull('rooms.custom_category_id')
            ->where('game_sessions.status', 'finished')
            ->groupBy('rooms.custom_category_id')
            ->selectRaw('rooms.custom_category_id as cid, COUNT(*) as cnt')
            ->get();

        foreach ($rows as $row) {
            if (! $row->cid) {
                continue;
            }
            DB::table('custom_categories')->where('id', $row->cid)->update(['usage_count' => (int) $row->cnt]);
        }
    }

    /**
     * Match runtime logic: count finished custom-room sessions whose question_ids include the question.
     */
    private function backfillQuestionUsage(): void
    {
        $questions = DB::table('custom_questions')->select('id', 'custom_category_id')->get();

        foreach ($questions as $q) {
            if (! $q->custom_category_id) {
                continue;
            }
            $cnt = GameSession::query()
                ->join('rooms', 'rooms.id', '=', 'game_sessions.room_id')
                ->where('rooms.is_custom', true)
                ->where('rooms.custom_category_id', $q->custom_category_id)
                ->where('game_sessions.status', 'finished')
                ->whereJsonContains('game_sessions.question_ids', (int) $q->id)
                ->count();

            DB::table('custom_questions')->where('id', $q->id)->update(['usage_count' => $cnt]);
        }
    }

    public function down(): void
    {
        Schema::table('custom_categories', function (Blueprint $table) {
            $table->dropColumn('usage_count');
        });

        Schema::table('custom_questions', function (Blueprint $table) {
            $table->dropColumn('usage_count');
        });
    }
};

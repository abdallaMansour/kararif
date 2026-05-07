<?php

use App\Models\CustomStage;
use App\Models\Stage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const VIDEO_COLLECTIONS = [
        'start_video',
        'end_video',
        'lunch_video',
        'correct_answer_video',
        'wrong_answer_video',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('custom_stages') || ! Schema::hasTable('stages')) {
            return;
        }

        if (CustomStage::query()->exists()) {
            return;
        }

        Stage::query()
            ->where('stage_type', Stage::TYPE_LIFE_POINTS)
            ->orderBy('id')
            ->each(function (Stage $stage): void {
                $customStage = CustomStage::create([
                    'name' => $stage->name,
                    'life_points_per_question' => $stage->life_points_per_question ?? 5,
                    'number_of_questions' => $stage->number_of_questions,
                    'status' => (bool) $stage->status,
                ]);

                foreach (self::VIDEO_COLLECTIONS as $collection) {
                    $media = $stage->getFirstMedia($collection);
                    if (! $media) {
                        continue;
                    }
                    $path = $media->getPath();
                    if (! is_string($path) || ! is_file($path)) {
                        continue;
                    }
                    try {
                        $customStage->addMedia($path)
                            ->preservingOriginal()
                            ->usingName($media->name)
                            ->usingFileName($media->file_name)
                            ->toMediaCollection($collection);
                    } catch (\Throwable) {
                        // Skip unreadable or missing files
                    }
                }
            });
    }

    public function down(): void
    {
        // Data migration: no automatic rollback
    }
};

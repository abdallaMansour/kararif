<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RemoveDuplicateQuestions extends Command
{
    protected $signature = 'question:remove-duplicates
        {--dry-run : Show what would be deleted without deleting}
        {--chunk=500 : Number of duplicate IDs to delete per batch}';

    protected $description = 'Remove duplicate questions by type/category/subcategory/content and keep one record per group';

    public function handle(): int
    {
        $groups = DB::table('questions')
            ->select([
                'type_id',
                'category_id',
                'subcategory_id',
                'name',
                DB::raw('MIN(id) as keep_id'),
                DB::raw('COUNT(*) as duplicates_count'),
            ])
            ->groupBy('type_id', 'category_id', 'subcategory_id', 'name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($groups->isEmpty()) {
            $this->info('No duplicate questions found.');
            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $chunkSize = max(1, (int) $this->option('chunk'));
        $idsToDelete = collect();
        $totalDuplicates = 0;

        foreach ($groups as $group) {
            $duplicateIds = Question::query()
                ->where('type_id', $group->type_id)
                ->where('category_id', $group->category_id)
                ->where('subcategory_id', $group->subcategory_id)
                ->where('name', $group->name)
                ->where('id', '!=', $group->keep_id)
                ->orderBy('id')
                ->pluck('id');

            $idsToDelete = $idsToDelete->merge($duplicateIds);
            $totalDuplicates += $duplicateIds->count();
        }

        $this->info('Duplicate groups found: '.$groups->count());
        $this->info('Duplicate rows to delete: '.$totalDuplicates);

        if ($dryRun) {
            $this->line('Dry run enabled. No rows were deleted.');
            $this->printSample($idsToDelete);
            return self::SUCCESS;
        }

        DB::transaction(function () use ($idsToDelete, $chunkSize): void {
            $idsToDelete
                ->chunk($chunkSize)
                ->each(function (Collection $chunk): void {
                    Question::whereIn('id', $chunk->all())->delete();
                });
        });

        $this->info('Duplicates removed successfully.');
        $this->printSample($idsToDelete);

        return self::SUCCESS;
    }

    private function printSample(Collection $idsToDelete): void
    {
        if ($idsToDelete->isEmpty()) {
            return;
        }

        $sample = $idsToDelete->take(20)->implode(', ');
        $this->line('Sample deleted IDs: '.$sample);

        if ($idsToDelete->count() > 20) {
            $this->line('...and '.($idsToDelete->count() - 20).' more.');
        }
    }
}

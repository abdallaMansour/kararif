<?php

namespace App\Console\Commands;

use App\Models\Question;
use App\Models\Type;
use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Console\Command;

class DuplicateQuestionForAllTypesCategories extends Command
{
    protected $signature = 'question:duplicate-for-all {question_id=2965}';

    protected $description = 'Duplicate a question across all type/category/subcategory combinations';

    public function handle(): int
    {
        $questionId = (int) $this->argument('question_id');
        $source = Question::find($questionId);

        if (!$source) {
            $this->error("Question {$questionId} not found.");
            return 1;
        }

        $this->info("Duplicating question {$questionId} across all combinations...");
        $sourceKey = "{$source->type_id}:{$source->category_id}:{$source->subcategory_id}";

        $combinations = [];
        foreach (Type::where('status', true)->get() as $type) {
            foreach ($type->categories()->where('status', true)->get() as $category) {
                foreach ($category->subcategories()->where('status', true)->get() as $subcategory) {
                    $combinations[] = [
                        'type_id' => $type->id,
                        'category_id' => $category->id,
                        'subcategory_id' => $subcategory->id,
                    ];
                }
            }
        }

        $created = 0;
        $skipped = 0;
        foreach ($combinations as $combo) {
            $key = "{$combo['type_id']}:{$combo['category_id']}:{$combo['subcategory_id']}";
            if ($key === $sourceKey) {
                $this->line("  Skip (source): type {$combo['type_id']}, cat {$combo['category_id']}, sub {$combo['subcategory_id']}");
                $skipped++;
                continue;
            }

            $exists = Question::where('type_id', $combo['type_id'])
                ->where('category_id', $combo['category_id'])
                ->where('subcategory_id', $combo['subcategory_id'])
                ->where('name', $source->name)
                ->exists();

            if ($exists) {
                $this->line("  Skip (already exists): type {$combo['type_id']}, cat {$combo['category_id']}, sub {$combo['subcategory_id']}");
                $skipped++;
                continue;
            }

            $copy = Question::create([
                'type_id' => $combo['type_id'],
                'category_id' => $combo['category_id'],
                'subcategory_id' => $combo['subcategory_id'],
                'name' => $source->name,
                'question_kind' => $source->question_kind ?? Question::KIND_NORMAL,
                'word_data' => $source->word_data,
                'answer_1' => $source->answer_1,
                'is_correct_1' => $source->is_correct_1,
                'answer_2' => $source->answer_2,
                'is_correct_2' => $source->is_correct_2,
                'answer_3' => $source->answer_3,
                'is_correct_3' => $source->is_correct_3,
                'answer_4' => $source->answer_4,
                'is_correct_4' => $source->is_correct_4,
                'status' => $source->status,
            ]);

            foreach ($source->getMedia() as $media) {
                $media->copy($copy, $media->collection_name);
            }

            $this->line("  Created ID {$copy->id}: type {$combo['type_id']}, cat {$combo['category_id']}, sub {$combo['subcategory_id']}");
            $created++;
        }

        $this->info("Done. Created {$created} copies, skipped {$skipped}.");
        return 0;
    }
}

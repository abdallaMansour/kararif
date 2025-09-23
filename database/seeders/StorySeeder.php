<?php

namespace Database\Seeders;

use App\Models\Story;
use Illuminate\Database\Seeder;

class StorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Story::create([
            'title' => 'القصة',
        ])
            ->addMedia(__DIR__ . '/story/story.jpg')
            ->preservingOriginal()
            ->toMediaCollection();

        Story::create([
            'title' => 'القصة',
        ])
            ->addMedia(__DIR__ . '/story/story.jpg')
            ->preservingOriginal()
            ->toMediaCollection();

        Story::create([
            'title' => 'القصة',
        ])
            ->addMedia(__DIR__ . '/story/story.jpg')
            ->preservingOriginal()
            ->toMediaCollection();

        Story::create([
            'title' => 'القصة',
        ])
            ->addMedia(__DIR__ . '/story/story.jpg')
            ->preservingOriginal()
            ->toMediaCollection();
    }
}

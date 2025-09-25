<?php

namespace Database\Seeders;

use App\Models\FullStory;
use Illuminate\Database\Seeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FullStorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fullStories = [
            [
                'title' => 'Educational Building Blocks',
                'description' => 'Colorful building blocks that help children develop motor skills and creativity.',
                'type' => 3,
                'link' => 'https://example.com/building-blocks',
                'is_free' => true,
                // 'image' => 'https://example.com/building-blocks.jpg',
            ],
            [
                'title' => 'Interactive Learning Tablet',
                'description' => 'A child-friendly tablet with educational games and activities.',
                'type' => 3,
                'link' => 'https://example.com/learning-tablet',
                // 'image' => 'https://example.com/learning-tablet.jpg',
            ],
            [
                'title' => 'Musical Instruments Set',
                'description' => 'A complete set of musical instruments for kids to explore music.',
                'type' => 3,
                'link' => 'https://example.com/musical-instruments',
                // 'image' => 'https://example.com/musical-instruments.jpg',
            ],
            [
                'title' => 'Puzzle Games Collection',
                'description' => 'Various puzzle games that enhance problem-solving skills.',
                'type' => 3,
                'link' => 'https://example.com/puzzle-games',
                // 'image' => 'https://example.com/puzzle-games.jpg',
            ],
            [
                'title' => 'Science Experiment Kit',
                'description' => 'Safe science experiments for curious young minds.',
                'type' => 3,
                'link' => 'https://example.com/science-kit',
                // 'image' => 'https://example.com/science-kit.jpg',
            ],
        ];

        FullStory::insert($fullStories);

        // foreach ($fullStories as $fullStoryData) {
        //     $toy = FullStory::create($fullStoryData);

        //     // Add sample image if it exists
        //     $imagePath = base_path('database/seeders/toy/' . strtolower(str_replace(' ', '_', $fullStoryData['title'])) . '.jpg');
        //     if (file_exists($imagePath)) {
        //         $fullStory->addMedia($imagePath)->toMediaCollection();
        //     }
        // }
    }
}

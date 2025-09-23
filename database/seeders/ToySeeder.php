<?php

namespace Database\Seeders;

use App\Models\Toy;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ToySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $toys = [
            [
                'title' => 'Educational Building Blocks',
                'description' => 'Colorful building blocks that help children develop motor skills and creativity.',
                'type' => 3,
                'link' => 'https://example.com/building-blocks',
            ],
            [
                'title' => 'Interactive Learning Tablet',
                'description' => 'A child-friendly tablet with educational games and activities.',
                'type' => 3,
                'link' => 'https://example.com/learning-tablet',
            ],
            [
                'title' => 'Musical Instruments Set',
                'description' => 'A complete set of musical instruments for kids to explore music.',
                'type' => 3,
                'link' => 'https://example.com/musical-instruments',
            ],
            [
                'title' => 'Puzzle Games Collection',
                'description' => 'Various puzzle games that enhance problem-solving skills.',
                'type' => 3,
                'link' => 'https://example.com/puzzle-games',
            ],
            [
                'title' => 'Science Experiment Kit',
                'description' => 'Safe science experiments for curious young minds.',
                'type' => 3,
                'link' => 'https://example.com/science-kit',
            ],
        ];

        Toy::insert($toys);

        foreach ($toys as $toyData) {
            // $toy = Toy::create($toyData);
            
            // // Add sample image if it exists
            // $imagePath = base_path('database/seeders/toy/' . strtolower(str_replace(' ', '_', $toyData['title'])) . '.jpg');
            // if (file_exists($imagePath)) {
            //     $toy->addMedia($imagePath)->toMediaCollection();
            // }
        }
    }
}

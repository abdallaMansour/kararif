<?php

namespace Database\Seeders;

use App\Models\BookAvailability;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookAvailabilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookAvailabilities = [
            [
                'title' => 'Arabic Children Books Collection',
                'description' => 'A comprehensive collection of Arabic children books available in local libraries and bookstores.',
                'link' => 'https://example.com/arabic-books',
                'country' => 'Egypt',
            ],
            [
                'title' => 'English Learning Materials',
                'description' => 'Educational materials and books for learning English as a second language.',
                'link' => 'https://example.com/english-materials',
                'country' => 'United States',
            ],
            [
                'title' => 'Science and Nature Books',
                'description' => 'Interactive science books and nature guides for young readers.',
                'link' => 'https://example.com/science-books',
                'country' => 'Canada',
            ],
            [
                'title' => 'Mathematics Workbooks',
                'description' => 'Step-by-step mathematics workbooks for different grade levels.',
                'link' => 'https://example.com/math-workbooks',
                'country' => 'United Kingdom',
            ],
            [
                'title' => 'Story Books and Novels',
                'description' => 'Classic and contemporary story books for children and young adults.',
                'link' => 'https://example.com/story-books',
                'country' => 'Australia',
            ],
        ];

        foreach ($bookAvailabilities as $bookData) {
            $bookAvailability = BookAvailability::create($bookData);
            
            // Add sample image if it exists
            // $imagePath = base_path('database/seeders/book_availability/' . strtolower(str_replace(' ', '_', $bookData['title'])) . '.jpg');
            // if (file_exists($imagePath)) {
            //     $bookAvailability->addMedia($imagePath)->toMediaCollection();
            // }
        }
    }
}
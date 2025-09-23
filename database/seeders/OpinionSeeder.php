<?php

namespace Database\Seeders;

use App\Models\Opinion;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OpinionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $opinions = [
            [
                'name' => 'Ahmed Hassan',
                'email' => 'ahmed.hassan@example.com',
                'phone' => '+201234567890',
                'opinion' => 'Excellent educational content! My children love the interactive stories and the quality is outstanding.',
                'rate' => 5,
            ],
            [
                'name' => 'Sarah Johnson',
                'email' => 'sarah.johnson@example.com',
                'phone' => '+1234567890',
                'opinion' => 'Great platform for learning. The materials are well-organized and easy to follow.',
                'rate' => 4,
            ],
            [
                'name' => 'Mohamed Ali',
                'email' => null,
                'phone' => '+966501234567',
                'opinion' => 'Very helpful resources for teaching Arabic to children. Highly recommended!',
                'rate' => 5,
            ],
            [
                'name' => 'Emily Davis',
                'email' => 'emily.davis@example.com',
                'phone' => null,
                'opinion' => 'Good content but could use more variety in the activities. Overall satisfied.',
                'rate' => 3,
            ],
            [
                'name' => 'Omar Khalil',
                'email' => 'omar.khalil@example.com',
                'phone' => '+971501234567',
                'opinion' => 'Amazing collection of books and educational materials. My kids are always excited to learn!',
                'rate' => 5,
            ],
            [
                'name' => 'Lisa Brown',
                'email' => 'lisa.brown@example.com',
                'phone' => '+441234567890',
                'opinion' => 'The platform is user-friendly and the content is age-appropriate. Great job!',
                'rate' => 4,
            ],
            [
                'name' => 'Hassan Mahmoud',
                'email' => null,
                'phone' => '+201987654321',
                'opinion' => 'Perfect for Arabic language learning. The stories are engaging and educational.',
                'rate' => 5,
            ],
            [
                'name' => 'Jennifer Wilson',
                'email' => 'jennifer.wilson@example.com',
                'phone' => '+15551234567',
                'opinion' => 'Good quality content but the interface could be improved for better navigation.',
                'rate' => 3,
            ],
        ];

        foreach ($opinions as $opinionData) {
            Opinion::create($opinionData);
        }
    }
}
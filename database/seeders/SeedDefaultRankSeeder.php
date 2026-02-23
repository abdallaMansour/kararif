<?php

namespace Database\Seeders;

use App\Models\Rank;
use Illuminate\Database\Seeder;

class SeedDefaultRankSeeder extends Seeder
{
    public function run(): void
    {
        Rank::firstOrCreate(
            ['start_score' => 0],
            ['name' => 'Starter', 'start_score' => 0]
        );
    }
}

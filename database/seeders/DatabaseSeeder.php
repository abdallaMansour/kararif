<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        // Delete old images and files from storage
        $this->clearDirectories();

        $this->call(PermissionsSeeder::class);
        $this->call(SeoDatabaseSeeder::class);
        $this->call(SettingDatabaseSeeder::class);
        $this->call(ContactUsSeeder::class);
        $this->call(UserDatabaseSeeder::class);
        $this->call(AboutUsSeeder::class);
    }


    public function clearDirectories()
    {
        // Path to the storage/app/public directory
        $storagePath = storage_path('app/public');

        // Delete all folders inside the storage path
        $folders = File::directories($storagePath);

        foreach ($folders as $folder) {
            File::deleteDirectory($folder);
        }

        echo ("  All folders in the storage path have been deleted.\n\n");
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Kreait\Firebase\Contract\Database;

class TestFirebaseConnection extends Command
{
    protected $signature = 'firebase:test';

    protected $description = 'Test Firebase Realtime Database connection and write a test value';

    public function handle(): int
    {
        $url = config('firebase.projects.app.database.url');
        $credentials = config('firebase.projects.app.credentials');

        $this->info('FIREBASE_DATABASE_URL: ' . ($url ?: '(not set)'));
        $this->info('FIREBASE_CREDENTIALS: ' . ($credentials ? 'set' : '(not set)'));

        if ($credentials && !str_starts_with($credentials, '{')) {
            $path = base_path($credentials);
            $this->info('Credentials file path: ' . $path);
            $this->info('File exists: ' . (file_exists($path) ? 'yes' : 'NO'));
        }

        if (!$url) {
            $this->error('FIREBASE_DATABASE_URL is not set. Run: php artisan config:clear');
            return 1;
        }

        try {
            $db = app(Database::class);
            $ref = $db->getReference('_test_connection');
            $ref->set(['timestamp' => time(), 'message' => 'Firebase connection OK']);
            $this->info('Success: Wrote test data to Firebase at /_test_connection');
            $ref->remove();
            $this->info('Cleaned up test data.');
            return 0;
        } catch (\Throwable $e) {
            $this->error('Firebase connection failed: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return 1;
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Support\Arr;
use Illuminate\Database\Seeder;

class UserDatabaseSeeder extends Seeder
{

    private $admins = [
        'Developer' => 'dev',
        'Abdalla' => 'abdalla',
        'Waly' => 'waly',
        'ACE' => 'ace',
        'Mommen' => 'mommen',
        'Info' => 'info',
        'Test' => 'test',
    ];

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->admins as $name => $email) {
            $user = User::factory()->create([
                'name' => $name,
                'email' => "$email@$email.com",
            ]);

            $user->syncRoles(['super_admin']);

            $user->save();
        }
    }
}

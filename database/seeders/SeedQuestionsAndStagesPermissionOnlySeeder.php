<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use App\Models\PermissionTranslation;

/**
 * Seeds only the "questions_and_stages" permission and attaches it to super_admin.
 * Run with: php artisan db:seed --class=SeedQuestionsAndStagesPermissionOnlySeeder
 */
class SeedQuestionsAndStagesPermissionOnlySeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'questions_and_stages'],
            ['name' => 'questions_and_stages']
        );

        $permission->translations()->delete();
        foreach (
            [
                ['locale' => 'ar', 'display_name' => 'الأسئلة والمراحل'],
                ['locale' => 'en', 'display_name' => 'Questions and Stages'],
            ] as $translation
        ) {
            PermissionTranslation::create([
                'display_name' => $translation['display_name'],
                'locale' => $translation['locale'],
                'permission_id' => $permission->id,
            ]);
        }

        $role = Role::firstOrCreate(
            ['name' => 'questions_and_stages'],
            ['name' => 'questions_and_stages']
        );
        $role->translateOrNew('ar')->display_name = 'الأسئلة والمراحل';
        $role->translateOrNew('en')->display_name = 'Questions and Stages';
        $role->save();
        if (!$role->permissions()->where('permission_id', $permission->id)->exists()) {
            $role->permissions()->attach([$permission->id]);
        }

        $superAdmin = Role::where('name', 'super_admin')->first();
        if ($superAdmin && !$superAdmin->permissions()->where('permission_id', $permission->id)->exists()) {
            $superAdmin->permissions()->attach([$permission->id]);
        }
    }
}

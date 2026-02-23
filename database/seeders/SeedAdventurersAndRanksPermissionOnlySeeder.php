<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use App\Models\PermissionTranslation;

class SeedAdventurersAndRanksPermissionOnlySeeder extends Seeder
{
    public function run(): void
    {
        foreach (
            [
                ['name' => 'adventurers', 'ar' => 'المغامرون', 'en' => 'Adventurers'],
                ['name' => 'ranks', 'ar' => 'الرتب', 'en' => 'Ranks'],
            ] as $item
        ) {
            $permission = Permission::firstOrCreate(['name' => $item['name']], ['name' => $item['name']]);
            $permission->translations()->delete();
            foreach ([['locale' => 'ar', 'display_name' => $item['ar']], ['locale' => 'en', 'display_name' => $item['en']]] as $t) {
                PermissionTranslation::create([
                    'display_name' => $t['display_name'],
                    'locale' => $t['locale'],
                    'permission_id' => $permission->id,
                ]);
            }
            $role = Role::firstOrCreate(['name' => $item['name']], ['name' => $item['name']]);
            $role->translateOrNew('ar')->display_name = $item['ar'];
            $role->translateOrNew('en')->display_name = $item['en'];
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
}

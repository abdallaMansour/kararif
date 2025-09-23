<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Seeder;
use App\Models\PermissionTranslation;

class PermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $this->seed($this->permissions());
    }

    public function seed(array $permissions = []): void
    {
        $permissions_array = [];

        foreach ($permissions as $permission) {
            $model = Permission::firstOrCreate(['name' => $permission['name']]);

            $model->translations()->delete();

            foreach ($permission['translations'] as $translation) {
                PermissionTranslation::create([
                    'display_name' => $translation['display_name'],
                    'locale' => $translation['locale'],
                    'permission_id' => $model->id,
                ]);
            }

            $role = Role::create(['name' => $permission['name']]);

            $role->translateOrNew('ar')->display_name =  $permission['display_name_ar'];
            $role->translateOrNew('en')->display_name =  $permission['display_name_en'];

            $role->permissions()->attach([$model->id]);

            $role->save();

            $permissions_array[] = $model->id;
        }

        $role = Role::create(['name' => 'super_admin']);

        $role->translateOrNew('ar')->display_name =  'المشرف الأعلى';
        $role->translateOrNew('en')->display_name =  'Super admin';

        $role->permissions()->attach($permissions_array);

        $role->save();
    }

    private function permissions(): array
    {
        return [
            [
                'name' => 'about_us',
                'display_name_ar' => 'من نحن',
                'display_name_en' => 'About us',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'من نحن'],
                    ['locale' => 'en', 'display_name' => 'About us'],
                ],
            ],
            [
                'name' => 'admin',
                'display_name_ar' => 'الإدارة',
                'display_name_en' => 'admin',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'الإدارة'],
                    ['locale' => 'en', 'display_name' => 'Admin'],
                ],
            ],
            [
                'name' => 'contact_us',
                'display_name_ar' => 'إتصل بنا',
                'display_name_en' => 'contact_us',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'إتصل بنا'],
                    ['locale' => 'en', 'display_name' => 'Contact us'],
                ],
            ],
            [
                'name' => 'setting',
                'display_name_ar' => 'الإعدادات',
                'display_name_en' => 'setting',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'الإعدادات'],
                    ['locale' => 'en', 'display_name' => 'Setting'],
                ],
            ],
            [
                'name' => 'role',
                'display_name_ar' => 'القواعد',
                'display_name_en' => 'role',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'القواعد'],
                    ['locale' => 'en', 'display_name' => 'Role'],
                ],
            ],
            [
                'name' => 'seo',
                'display_name_ar' => 'البحث',
                'display_name_en' => 'SEO',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'البحث'],
                    ['locale' => 'en', 'display_name' => 'SEO'],
                ],
            ],
        ];
    }
}

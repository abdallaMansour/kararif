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
                'name' => 'author',
                'display_name_ar' => 'المؤلف',
                'display_name_en' => 'Author',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'المؤلف'],
                    ['locale' => 'en', 'display_name' => 'Author'],
                ],
            ],
            [
                'name' => 'story',
                'display_name_ar' => 'القصة',
                'display_name_en' => 'Story',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'القصة'],
                    ['locale' => 'en', 'display_name' => 'Story'],
                ],
            ],
            [
                'name' => 'toy',
                'display_name_ar' => 'اللعبة',
                'display_name_en' => 'Toy',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'اللعبة'],
                    ['locale' => 'en', 'display_name' => 'Toy'],
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
            [
                'name' => 'questions_and_stages',
                'display_name_ar' => 'الأسئلة والمراحل',
                'display_name_en' => 'Questions and Stages',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'الأسئلة والمراحل'],
                    ['locale' => 'en', 'display_name' => 'Questions and Stages'],
                ],
            ],
            [
                'name' => 'questions',
                'display_name_ar' => 'الأسئلة',
                'display_name_en' => 'Questions',
                'translations' => [
                    ['locale' => 'ar', 'display_name' => 'الأسئلة'],
                    ['locale' => 'en', 'display_name' => 'Questions'],
                ],
            ],
        ];
    }
}

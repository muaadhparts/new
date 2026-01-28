<?php

namespace App\Domain\Catalog\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Catalog\Models\Category;

/**
 * Category Seeder
 *
 * Seeds auto parts categories hierarchy.
 */
class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Engine Parts',
                'name_ar' => 'قطع المحرك',
                'children' => [
                    ['name' => 'Oil Filters', 'name_ar' => 'فلاتر الزيت'],
                    ['name' => 'Air Filters', 'name_ar' => 'فلاتر الهواء'],
                    ['name' => 'Spark Plugs', 'name_ar' => 'شمعات الإشعال'],
                    ['name' => 'Belts & Hoses', 'name_ar' => 'السيور والخراطيم'],
                    ['name' => 'Gaskets', 'name_ar' => 'الجوانات'],
                ],
            ],
            [
                'name' => 'Brake System',
                'name_ar' => 'نظام الفرامل',
                'children' => [
                    ['name' => 'Brake Pads', 'name_ar' => 'تيل الفرامل'],
                    ['name' => 'Brake Discs', 'name_ar' => 'أقراص الفرامل'],
                    ['name' => 'Brake Fluid', 'name_ar' => 'زيت الفرامل'],
                    ['name' => 'Brake Calipers', 'name_ar' => 'فكوك الفرامل'],
                ],
            ],
            [
                'name' => 'Suspension',
                'name_ar' => 'نظام التعليق',
                'children' => [
                    ['name' => 'Shock Absorbers', 'name_ar' => 'مساعدات'],
                    ['name' => 'Springs', 'name_ar' => 'سوست'],
                    ['name' => 'Control Arms', 'name_ar' => 'مقصات'],
                    ['name' => 'Ball Joints', 'name_ar' => 'روتيلات'],
                ],
            ],
            [
                'name' => 'Electrical',
                'name_ar' => 'الكهرباء',
                'children' => [
                    ['name' => 'Batteries', 'name_ar' => 'بطاريات'],
                    ['name' => 'Alternators', 'name_ar' => 'دينامو'],
                    ['name' => 'Starters', 'name_ar' => 'سلف'],
                    ['name' => 'Sensors', 'name_ar' => 'حساسات'],
                    ['name' => 'Lights', 'name_ar' => 'إضاءة'],
                ],
            ],
            [
                'name' => 'Body Parts',
                'name_ar' => 'قطع الهيكل',
                'children' => [
                    ['name' => 'Bumpers', 'name_ar' => 'صدامات'],
                    ['name' => 'Mirrors', 'name_ar' => 'مرايا'],
                    ['name' => 'Fenders', 'name_ar' => 'رفارف'],
                    ['name' => 'Hoods', 'name_ar' => 'كبوت'],
                ],
            ],
            [
                'name' => 'Cooling System',
                'name_ar' => 'نظام التبريد',
                'children' => [
                    ['name' => 'Radiators', 'name_ar' => 'رديتر'],
                    ['name' => 'Water Pumps', 'name_ar' => 'طرمبة ماء'],
                    ['name' => 'Thermostats', 'name_ar' => 'ثرموستات'],
                    ['name' => 'Coolant', 'name_ar' => 'ماء رديتر'],
                ],
            ],
            [
                'name' => 'Oils & Fluids',
                'name_ar' => 'الزيوت والسوائل',
                'children' => [
                    ['name' => 'Engine Oil', 'name_ar' => 'زيت محرك'],
                    ['name' => 'Transmission Oil', 'name_ar' => 'زيت قير'],
                    ['name' => 'Power Steering Fluid', 'name_ar' => 'زيت باور'],
                ],
            ],
        ];

        $sortOrder = 1;
        foreach ($categories as $category) {
            $parent = Category::firstOrCreate(
                ['slug' => \Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'name_ar' => $category['name_ar'],
                    'slug' => \Str::slug($category['name']),
                    'level' => 1,
                    'parent_id' => null,
                    'status' => 1,
                    'sort_order' => $sortOrder++,
                ]
            );

            if (isset($category['children'])) {
                $childOrder = 1;
                foreach ($category['children'] as $child) {
                    Category::firstOrCreate(
                        ['slug' => \Str::slug($child['name'])],
                        [
                            'name' => $child['name'],
                            'name_ar' => $child['name_ar'],
                            'slug' => \Str::slug($child['name']),
                            'level' => 2,
                            'parent_id' => $parent->id,
                            'status' => 1,
                            'sort_order' => $childOrder++,
                        ]
                    );
                }
            }
        }

        $this->command->info('Seeded categories with children.');
    }
}

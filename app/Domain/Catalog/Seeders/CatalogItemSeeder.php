<?php

namespace App\Domain\Catalog\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Catalog\Models\CatalogItem;
use App\Domain\Catalog\Models\Brand;
use App\Domain\Catalog\Models\NewCategory;

/**
 * Catalog Item Seeder
 *
 * Seeds sample catalog items.
 */
class CatalogItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = Brand::where('status', 1)->pluck('id')->toArray();
        $categories = NewCategory::where('status', 1)->where('level', 2)->pluck('id')->toArray();

        if (empty($brands) || empty($categories)) {
            $this->command->warn('Please run BrandSeeder and CategorySeeder first.');
            return;
        }

        $items = [
            ['name' => 'Oil Filter Premium', 'name_ar' => 'فلتر زيت بريميوم', 'sku' => 'OIL-FLT-001'],
            ['name' => 'Air Filter Standard', 'name_ar' => 'فلتر هواء ستاندرد', 'sku' => 'AIR-FLT-001'],
            ['name' => 'Brake Pad Set Front', 'name_ar' => 'طقم تيل فرامل أمامي', 'sku' => 'BRK-PAD-001'],
            ['name' => 'Brake Pad Set Rear', 'name_ar' => 'طقم تيل فرامل خلفي', 'sku' => 'BRK-PAD-002'],
            ['name' => 'Spark Plug Iridium', 'name_ar' => 'شمعة إيريديوم', 'sku' => 'SPK-PLG-001'],
            ['name' => 'Battery 60Ah', 'name_ar' => 'بطارية 60 أمبير', 'sku' => 'BAT-60A-001'],
            ['name' => 'Shock Absorber Front', 'name_ar' => 'مساعد أمامي', 'sku' => 'SHK-FRT-001'],
            ['name' => 'Shock Absorber Rear', 'name_ar' => 'مساعد خلفي', 'sku' => 'SHK-RER-001'],
            ['name' => 'Radiator Assembly', 'name_ar' => 'رديتر كامل', 'sku' => 'RAD-ASM-001'],
            ['name' => 'Water Pump', 'name_ar' => 'طرمبة ماء', 'sku' => 'WTR-PMP-001'],
            ['name' => 'Alternator 12V', 'name_ar' => 'دينامو 12 فولت', 'sku' => 'ALT-12V-001'],
            ['name' => 'Starter Motor', 'name_ar' => 'سلف', 'sku' => 'STR-MTR-001'],
            ['name' => 'Engine Oil 5W30', 'name_ar' => 'زيت محرك 5W30', 'sku' => 'OIL-5W30-001'],
            ['name' => 'Transmission Oil ATF', 'name_ar' => 'زيت قير أوتوماتيك', 'sku' => 'OIL-ATF-001'],
            ['name' => 'Timing Belt Kit', 'name_ar' => 'طقم سير توقيت', 'sku' => 'TIM-BLT-001'],
        ];

        foreach ($items as $index => $item) {
            CatalogItem::firstOrCreate(
                ['sku' => $item['sku']],
                [
                    'name' => $item['name'],
                    'name_ar' => $item['name_ar'],
                    'sku' => $item['sku'],
                    'slug' => \Str::slug($item['name']),
                    'part_number' => 'PN-' . str_pad($index + 1, 6, '0', STR_PAD_LEFT),
                    'description' => 'High quality ' . strtolower($item['name']) . ' for various vehicle models.',
                    'description_ar' => $item['name_ar'] . ' عالي الجودة لمختلف الموديلات.',
                    'brand_id' => $brands[array_rand($brands)],
                    'new_category_id' => $categories[array_rand($categories)],
                    'status' => 1,
                ]
            );
        }

        $this->command->info('Seeded ' . count($items) . ' catalog items.');
    }
}

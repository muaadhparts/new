<?php

namespace App\Domain\Shipping\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Shipping\Models\City;
use App\Domain\Shipping\Models\Country;

/**
 * City Seeder
 *
 * Seeds major Saudi Arabian cities.
 */
class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $saudiArabia = Country::where('code', 'SA')->first();

        if (!$saudiArabia) {
            $this->command->warn('Please run CountrySeeder first.');
            return;
        }

        $cities = [
            ['name' => 'Riyadh', 'name_ar' => 'الرياض'],
            ['name' => 'Jeddah', 'name_ar' => 'جدة'],
            ['name' => 'Mecca', 'name_ar' => 'مكة المكرمة'],
            ['name' => 'Medina', 'name_ar' => 'المدينة المنورة'],
            ['name' => 'Dammam', 'name_ar' => 'الدمام'],
            ['name' => 'Khobar', 'name_ar' => 'الخبر'],
            ['name' => 'Dhahran', 'name_ar' => 'الظهران'],
            ['name' => 'Taif', 'name_ar' => 'الطائف'],
            ['name' => 'Tabuk', 'name_ar' => 'تبوك'],
            ['name' => 'Buraidah', 'name_ar' => 'بريدة'],
            ['name' => 'Khamis Mushait', 'name_ar' => 'خميس مشيط'],
            ['name' => 'Abha', 'name_ar' => 'أبها'],
            ['name' => 'Najran', 'name_ar' => 'نجران'],
            ['name' => 'Jazan', 'name_ar' => 'جازان'],
            ['name' => 'Hail', 'name_ar' => 'حائل'],
            ['name' => 'Jubail', 'name_ar' => 'الجبيل'],
            ['name' => 'Yanbu', 'name_ar' => 'ينبع'],
            ['name' => 'Al Hofuf', 'name_ar' => 'الهفوف'],
            ['name' => 'Al Qatif', 'name_ar' => 'القطيف'],
            ['name' => 'Sakaka', 'name_ar' => 'سكاكا'],
        ];

        foreach ($cities as $city) {
            City::firstOrCreate(
                ['name' => $city['name'], 'country_id' => $saudiArabia->id],
                array_merge($city, [
                    'country_id' => $saudiArabia->id,
                    'status' => 1,
                ])
            );
        }

        $this->command->info('Seeded ' . count($cities) . ' Saudi cities.');
    }
}

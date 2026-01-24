<?php

namespace App\Domain\Shipping\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Shipping\Models\Country;

/**
 * Country Seeder
 *
 * Seeds GCC and common countries.
 */
class CountrySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $countries = [
            ['name' => 'Saudi Arabia', 'name_ar' => 'المملكة العربية السعودية', 'code' => 'SA', 'phone_code' => '+966'],
            ['name' => 'United Arab Emirates', 'name_ar' => 'الإمارات العربية المتحدة', 'code' => 'AE', 'phone_code' => '+971'],
            ['name' => 'Kuwait', 'name_ar' => 'الكويت', 'code' => 'KW', 'phone_code' => '+965'],
            ['name' => 'Bahrain', 'name_ar' => 'البحرين', 'code' => 'BH', 'phone_code' => '+973'],
            ['name' => 'Qatar', 'name_ar' => 'قطر', 'code' => 'QA', 'phone_code' => '+974'],
            ['name' => 'Oman', 'name_ar' => 'عمان', 'code' => 'OM', 'phone_code' => '+968'],
            ['name' => 'Egypt', 'name_ar' => 'مصر', 'code' => 'EG', 'phone_code' => '+20'],
            ['name' => 'Jordan', 'name_ar' => 'الأردن', 'code' => 'JO', 'phone_code' => '+962'],
        ];

        foreach ($countries as $country) {
            Country::firstOrCreate(
                ['code' => $country['code']],
                array_merge($country, ['status' => 1])
            );
        }

        $this->command->info('Seeded ' . count($countries) . ' countries.');
    }
}

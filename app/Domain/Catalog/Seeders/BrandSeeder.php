<?php

namespace App\Domain\Catalog\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Catalog\Models\Brand;

/**
 * Brand Seeder
 *
 * Seeds popular auto parts brands.
 */
class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            ['name' => 'Toyota', 'name_ar' => 'تويوتا', 'slug' => 'toyota'],
            ['name' => 'Honda', 'name_ar' => 'هوندا', 'slug' => 'honda'],
            ['name' => 'Nissan', 'name_ar' => 'نيسان', 'slug' => 'nissan'],
            ['name' => 'Hyundai', 'name_ar' => 'هيونداي', 'slug' => 'hyundai'],
            ['name' => 'Kia', 'name_ar' => 'كيا', 'slug' => 'kia'],
            ['name' => 'Ford', 'name_ar' => 'فورد', 'slug' => 'ford'],
            ['name' => 'Chevrolet', 'name_ar' => 'شيفروليه', 'slug' => 'chevrolet'],
            ['name' => 'BMW', 'name_ar' => 'بي إم دبليو', 'slug' => 'bmw'],
            ['name' => 'Mercedes-Benz', 'name_ar' => 'مرسيدس بنز', 'slug' => 'mercedes-benz'],
            ['name' => 'Lexus', 'name_ar' => 'لكزس', 'slug' => 'lexus'],
            ['name' => 'GMC', 'name_ar' => 'جي إم سي', 'slug' => 'gmc'],
            ['name' => 'Mazda', 'name_ar' => 'مازدا', 'slug' => 'mazda'],
            ['name' => 'Mitsubishi', 'name_ar' => 'ميتسوبيشي', 'slug' => 'mitsubishi'],
            ['name' => 'Isuzu', 'name_ar' => 'إيسوزو', 'slug' => 'isuzu'],
            ['name' => 'Bosch', 'name_ar' => 'بوش', 'slug' => 'bosch'],
            ['name' => 'Denso', 'name_ar' => 'دينسو', 'slug' => 'denso'],
            ['name' => 'NGK', 'name_ar' => 'إن جي كي', 'slug' => 'ngk'],
            ['name' => 'Mobil', 'name_ar' => 'موبيل', 'slug' => 'mobil'],
            ['name' => 'Castrol', 'name_ar' => 'كاسترول', 'slug' => 'castrol'],
            ['name' => 'ACDelco', 'name_ar' => 'إيه سي ديلكو', 'slug' => 'acdelco'],
        ];

        foreach ($brands as $brand) {
            Brand::firstOrCreate(
                ['slug' => $brand['slug']],
                array_merge($brand, ['status' => 1])
            );
        }

        $this->command->info('Seeded ' . count($brands) . ' brands.');
    }
}

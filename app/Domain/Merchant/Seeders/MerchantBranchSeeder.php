<?php

namespace App\Domain\Merchant\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Merchant\Models\MerchantBranch;
use App\Domain\Identity\Models\User;
use App\Domain\Shipping\Models\City;

/**
 * Merchant Branch Seeder
 *
 * Seeds merchant branches.
 */
class MerchantBranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchants = User::where('is_merchant', 1)->get();

        if ($merchants->isEmpty()) {
            $this->command->warn('Please run UserSeeder first.');
            return;
        }

        $cities = City::where('status', 1)->pluck('id', 'name')->toArray();

        if (empty($cities)) {
            $this->command->warn('Please run CitySeeder first.');
            return;
        }

        $branchData = [
            'riyadh-parts@example.com' => [
                ['name' => 'Main Branch', 'city' => 'Riyadh', 'is_main' => 1],
                ['name' => 'North Branch', 'city' => 'Riyadh', 'is_main' => 0],
            ],
            'jeddah-auto@example.com' => [
                ['name' => 'Jeddah Main', 'city' => 'Jeddah', 'is_main' => 1],
            ],
            'dammam-parts@example.com' => [
                ['name' => 'Dammam Main', 'city' => 'Dammam', 'is_main' => 1],
                ['name' => 'Khobar Branch', 'city' => 'Khobar', 'is_main' => 0],
            ],
        ];

        $count = 0;
        foreach ($merchants as $merchant) {
            $branches = $branchData[$merchant->email] ?? [
                ['name' => 'Main Branch', 'city' => 'Riyadh', 'is_main' => 1],
            ];

            foreach ($branches as $branch) {
                $cityId = $cities[$branch['city']] ?? array_values($cities)[0];

                MerchantBranch::firstOrCreate(
                    ['user_id' => $merchant->id, 'name' => $branch['name']],
                    [
                        'user_id' => $merchant->id,
                        'name' => $branch['name'],
                        'name_ar' => 'فرع ' . $branch['name'],
                        'city_id' => $cityId,
                        'address' => $branch['city'] . ' - Main Street',
                        'phone' => $merchant->phone,
                        'is_main' => $branch['is_main'],
                        'status' => 1,
                    ]
                );
                $count++;
            }
        }

        $this->command->info("Seeded {$count} merchant branches.");
    }
}

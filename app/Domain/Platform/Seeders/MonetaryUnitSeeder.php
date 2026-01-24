<?php

namespace App\Domain\Platform\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Platform\Models\MonetaryUnit;

/**
 * Monetary Unit Seeder
 *
 * Seeds supported currencies.
 */
class MonetaryUnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'name' => 'Saudi Riyal',
                'name_ar' => 'ريال سعودي',
                'code' => 'SAR',
                'sign' => 'ر.س',
                'exchange_rate' => 1.00,
                'is_default' => 1,
            ],
            [
                'name' => 'US Dollar',
                'name_ar' => 'دولار أمريكي',
                'code' => 'USD',
                'sign' => '$',
                'exchange_rate' => 0.27,
                'is_default' => 0,
            ],
            [
                'name' => 'UAE Dirham',
                'name_ar' => 'درهم إماراتي',
                'code' => 'AED',
                'sign' => 'د.إ',
                'exchange_rate' => 0.98,
                'is_default' => 0,
            ],
            [
                'name' => 'Kuwaiti Dinar',
                'name_ar' => 'دينار كويتي',
                'code' => 'KWD',
                'sign' => 'د.ك',
                'exchange_rate' => 0.082,
                'is_default' => 0,
            ],
            [
                'name' => 'Euro',
                'name_ar' => 'يورو',
                'code' => 'EUR',
                'sign' => '€',
                'exchange_rate' => 0.24,
                'is_default' => 0,
            ],
        ];

        foreach ($currencies as $currency) {
            MonetaryUnit::firstOrCreate(
                ['code' => $currency['code']],
                array_merge($currency, ['status' => 1])
            );
        }

        $this->command->info('Seeded ' . count($currencies) . ' currencies.');
    }
}

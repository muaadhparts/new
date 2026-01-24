<?php

namespace App\Domain\Shipping\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Shipping\Models\Courier;

/**
 * Courier Seeder
 *
 * Seeds shipping courier companies.
 */
class CourierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $couriers = [
            [
                'name' => 'SMSA Express',
                'code' => 'smsa',
                'tracking_url' => 'https://www.smsaexpress.com/track?awb={tracking_number}',
            ],
            [
                'name' => 'Aramex',
                'code' => 'aramex',
                'tracking_url' => 'https://www.aramex.com/track/results?ShipmentNumber={tracking_number}',
            ],
            [
                'name' => 'DHL',
                'code' => 'dhl',
                'tracking_url' => 'https://www.dhl.com/sa-en/home/tracking.html?tracking-id={tracking_number}',
            ],
            [
                'name' => 'FedEx',
                'code' => 'fedex',
                'tracking_url' => 'https://www.fedex.com/fedextrack/?trknbr={tracking_number}',
            ],
            [
                'name' => 'J&T Express',
                'code' => 'jnt',
                'tracking_url' => 'https://www.jtexpress.sa/track?bill={tracking_number}',
            ],
            [
                'name' => 'Naqel Express',
                'code' => 'naqel',
                'tracking_url' => 'https://www.naqelexpress.com/tracking/{tracking_number}',
            ],
            [
                'name' => 'Fetchr',
                'code' => 'fetchr',
                'tracking_url' => 'https://www.fetchr.us/tracking/{tracking_number}',
            ],
            [
                'name' => 'Zajil Express',
                'code' => 'zajil',
                'tracking_url' => 'https://www.zajil.com/track?awb={tracking_number}',
            ],
        ];

        foreach ($couriers as $courier) {
            Courier::firstOrCreate(
                ['code' => $courier['code']],
                array_merge($courier, ['status' => 1])
            );
        }

        $this->command->info('Seeded ' . count($couriers) . ' couriers.');
    }
}

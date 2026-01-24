<?php

namespace App\Domain\Merchant\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Merchant\Models\MerchantSetting;
use App\Domain\Identity\Models\User;

/**
 * Merchant Setting Seeder
 *
 * Seeds merchant settings.
 */
class MerchantSettingSeeder extends Seeder
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

        foreach ($merchants as $merchant) {
            MerchantSetting::firstOrCreate(
                ['user_id' => $merchant->id],
                [
                    'user_id' => $merchant->id,
                    'shop_name' => $merchant->name,
                    'shop_name_ar' => $merchant->name,
                    'shop_email' => $merchant->email,
                    'shop_phone' => $merchant->phone,
                    'shop_description' => 'Welcome to our auto parts store. We provide high-quality genuine and aftermarket parts.',
                    'min_order_amount' => 50,
                    'free_shipping_threshold' => 500,
                    'auto_accept_orders' => true,
                    'low_stock_threshold' => 5,
                ]
            );
        }

        $this->command->info('Seeded ' . $merchants->count() . ' merchant settings.');
    }
}

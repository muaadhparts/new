<?php

namespace App\Domain\Platform\Seeders;

use Illuminate\Database\Seeder;
use App\Domain\Platform\Models\PlatformSetting;

/**
 * Platform Setting Seeder
 *
 * Seeds default platform settings.
 */
class PlatformSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General
            ['group' => 'general', 'key' => 'site_name', 'value' => 'MUAADH EPC'],
            ['group' => 'general', 'key' => 'site_name_ar', 'value' => 'معاذ للقطع'],
            ['group' => 'general', 'key' => 'site_tagline', 'value' => 'Your Trusted Auto Parts Marketplace'],
            ['group' => 'general', 'key' => 'site_tagline_ar', 'value' => 'سوقك الموثوق لقطع الغيار'],
            ['group' => 'general', 'key' => 'contact_email', 'value' => 'support@muaadh.com'],
            ['group' => 'general', 'key' => 'contact_phone', 'value' => '+966500000000'],

            // Commerce
            ['group' => 'commerce', 'key' => 'tax_rate', 'value' => '15'],
            ['group' => 'commerce', 'key' => 'commission_rate', 'value' => '5'],
            ['group' => 'commerce', 'key' => 'min_order_amount', 'value' => '50'],
            ['group' => 'commerce', 'key' => 'free_shipping_threshold', 'value' => '500'],

            // Shipping
            ['group' => 'shipping', 'key' => 'default_shipping_cost', 'value' => '25'],
            ['group' => 'shipping', 'key' => 'express_shipping_cost', 'value' => '50'],

            // Merchant
            ['group' => 'merchant', 'key' => 'min_withdrawal_amount', 'value' => '100'],
            ['group' => 'merchant', 'key' => 'settlement_period_days', 'value' => '7'],

            // Features
            ['group' => 'features', 'key' => 'reviews_enabled', 'value' => '1'],
            ['group' => 'features', 'key' => 'guest_checkout', 'value' => '0'],
            ['group' => 'features', 'key' => 'multi_currency', 'value' => '1'],
        ];

        foreach ($settings as $setting) {
            PlatformSetting::firstOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                $setting
            );
        }

        $this->command->info('Seeded ' . count($settings) . ' platform settings.');
    }
}

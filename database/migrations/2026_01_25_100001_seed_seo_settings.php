<?php

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Seed SEO settings in platform_settings table.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            echo "  WARNING: platform_settings table does not exist. Skipping.\n";
            return;
        }

        $settings = [
            [
                'group' => 'seo',
                'key' => 'meta_keys',
                'value' => null,
                'type' => 'string',
                'description' => 'Default meta keywords for the site',
            ],
            [
                'group' => 'seo',
                'key' => 'meta_description',
                'value' => null,
                'type' => 'string',
                'description' => 'Default meta description for the site',
            ],
            [
                'group' => 'seo',
                'key' => 'google_analytics',
                'value' => null,
                'type' => 'string',
                'description' => 'Google Analytics tracking ID (GA4)',
            ],
            [
                'group' => 'seo',
                'key' => 'gtm_id',
                'value' => null,
                'type' => 'string',
                'description' => 'Google Tag Manager container ID',
            ],
            [
                'group' => 'seo',
                'key' => 'facebook_pixel',
                'value' => null,
                'type' => 'string',
                'description' => 'Facebook Pixel ID',
            ],
            [
                'group' => 'seo',
                'key' => 'search_console_verification',
                'value' => null,
                'type' => 'string',
                'description' => 'Google Search Console verification code',
            ],
            [
                'group' => 'seo',
                'key' => 'bing_verification',
                'value' => null,
                'type' => 'string',
                'description' => 'Bing Webmaster verification code',
            ],
        ];

        $count = 0;
        foreach ($settings as $setting) {
            // Only create if doesn't exist
            $exists = PlatformSetting::where('group', $setting['group'])
                ->where('key', $setting['key'])
                ->exists();

            if (!$exists) {
                PlatformSetting::create($setting);
                $count++;
            }
        }

        echo "  Added {$count} SEO settings.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        PlatformSetting::where('group', 'seo')->delete();
    }
};

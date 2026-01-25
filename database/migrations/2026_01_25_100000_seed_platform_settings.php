<?php

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Seed platform_settings table with legacy settings data.
 *
 * This migration converts the old flat settings structure to the new
 * key-value pair structure with groups.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('platform_settings')) {
            echo "  WARNING: platform_settings table does not exist. Skipping seed.\n";
            return;
        }

        // Check if already seeded
        if (PlatformSetting::count() > 0) {
            echo "  Skipping: platform_settings already has data.\n";
            return;
        }

        $settings = $this->getLegacySettings();

        foreach ($settings as $setting) {
            PlatformSetting::create($setting);
        }

        echo "  Seeded " . count($settings) . " platform settings.\n";
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only delete settings created by this migration
        $groups = ['branding', 'mail', 'currency', 'affiliate', 'withdraw',
                   'features', 'security', 'maintenance', 'merchant', 'debug', 'display'];

        PlatformSetting::whereIn('group', $groups)->delete();
    }

    /**
     * Get legacy settings mapped to new structure.
     */
    private function getLegacySettings(): array
    {
        return [
            // =====================================================================
            // BRANDING GROUP
            // =====================================================================
            [
                'group' => 'branding',
                'key' => 'logo',
                'value' => '17691223941735png.png',
                'type' => 'file',
                'description' => 'Main site logo',
            ],
            [
                'group' => 'branding',
                'key' => 'favicon',
                'value' => '17691224961759428502nissan600x600finalpngpng.png',
                'type' => 'file',
                'description' => 'Site favicon',
            ],
            [
                'group' => 'branding',
                'key' => 'site_name',
                'value' => 'MUAADH STOR',
                'type' => 'string',
                'description' => 'Site name displayed in header and title',
            ],
            [
                'group' => 'branding',
                'key' => 'loader',
                'value' => 'loader.gif',
                'type' => 'file',
                'description' => 'Frontend loading animation',
            ],
            [
                'group' => 'branding',
                'key' => 'admin_loader',
                'value' => null,
                'type' => 'file',
                'description' => 'Admin panel loading animation',
            ],
            [
                'group' => 'branding',
                'key' => 'footer_logo',
                'value' => '17691223961735png.png',
                'type' => 'file',
                'description' => 'Footer logo image',
            ],
            [
                'group' => 'branding',
                'key' => 'invoice_logo',
                'value' => '17691223981735png.png',
                'type' => 'file',
                'description' => 'Logo used on invoices',
            ],
            [
                'group' => 'branding',
                'key' => 'copyright',
                'value' => 'COPYRIGHT © 2024. All Rights Reserved By YEM',
                'type' => 'string',
                'description' => 'Copyright text in footer',
            ],
            [
                'group' => 'branding',
                'key' => 'user_image',
                'value' => null,
                'type' => 'file',
                'description' => 'Default user avatar image',
            ],

            // =====================================================================
            // MAIL GROUP
            // =====================================================================
            [
                'group' => 'mail',
                'key' => 'driver',
                'value' => null,
                'type' => 'string',
                'description' => 'Mail driver (smtp, mailgun, etc)',
            ],
            [
                'group' => 'mail',
                'key' => 'host',
                'value' => null,
                'type' => 'string',
                'description' => 'SMTP host address',
            ],
            [
                'group' => 'mail',
                'key' => 'port',
                'value' => null,
                'type' => 'integer',
                'description' => 'SMTP port number',
            ],
            [
                'group' => 'mail',
                'key' => 'encryption',
                'value' => null,
                'type' => 'string',
                'description' => 'Mail encryption (tls, ssl)',
            ],
            [
                'group' => 'mail',
                'key' => 'username',
                'value' => null,
                'type' => 'string',
                'description' => 'SMTP username',
            ],
            [
                'group' => 'mail',
                'key' => 'password',
                'value' => null,
                'type' => 'string',
                'description' => 'SMTP password',
            ],
            [
                'group' => 'mail',
                'key' => 'from_email',
                'value' => null,
                'type' => 'string',
                'description' => 'Default from email address',
            ],
            [
                'group' => 'mail',
                'key' => 'from_name',
                'value' => null,
                'type' => 'string',
                'description' => 'Default from name',
            ],

            // =====================================================================
            // CURRENCY GROUP
            // =====================================================================
            [
                'group' => 'currency',
                'key' => 'format',
                'value' => 0,
                'type' => 'integer',
                'description' => 'Currency display format (0=prefix, 1=suffix)',
            ],
            [
                'group' => 'currency',
                'key' => 'decimal_separator',
                'value' => null,
                'type' => 'string',
                'description' => 'Decimal separator character',
            ],
            [
                'group' => 'currency',
                'key' => 'thousand_separator',
                'value' => null,
                'type' => 'string',
                'description' => 'Thousand separator character',
            ],

            // =====================================================================
            // AFFILIATE GROUP
            // =====================================================================
            [
                'group' => 'affiliate',
                'key' => 'is_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable affiliate system',
            ],
            [
                'group' => 'affiliate',
                'key' => 'charge',
                'value' => 0,
                'type' => 'decimal',
                'description' => 'Affiliate commission rate',
            ],
            [
                'group' => 'affiliate',
                'key' => 'banner',
                'value' => null,
                'type' => 'file',
                'description' => 'Affiliate program banner image',
            ],

            // =====================================================================
            // WITHDRAW GROUP
            // =====================================================================
            [
                'group' => 'withdraw',
                'key' => 'fee',
                'value' => 0,
                'type' => 'decimal',
                'description' => 'Withdrawal fee percentage',
            ],
            [
                'group' => 'withdraw',
                'key' => 'charge',
                'value' => 0,
                'type' => 'decimal',
                'description' => 'Fixed withdrawal charge',
            ],

            // =====================================================================
            // FEATURES GROUP
            // =====================================================================
            [
                'group' => 'features',
                'key' => 'talkto_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable TalkTo chat integration',
            ],
            [
                'group' => 'features',
                'key' => 'talkto_script',
                'value' => null,
                'type' => 'string',
                'description' => 'TalkTo script/widget code',
            ],
            [
                'group' => 'features',
                'key' => 'buyer_notes_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Allow buyer notes on orders',
            ],
            [
                'group' => 'features',
                'key' => 'multi_currency_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable multiple currencies',
            ],
            [
                'group' => 'features',
                'key' => 'wholesell_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable wholesale pricing',
            ],
            [
                'group' => 'features',
                'key' => 'popup_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Show popup on homepage',
            ],
            [
                'group' => 'features',
                'key' => 'report_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable item reporting',
            ],
            [
                'group' => 'features',
                'key' => 'cookie_consent_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Show cookie consent banner',
            ],
            [
                'group' => 'features',
                'key' => 'item_verification_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Require item verification before publish',
            ],
            [
                'group' => 'features',
                'key' => 'email_verification_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Require email verification on registration',
            ],

            // =====================================================================
            // SECURITY GROUP
            // =====================================================================
            [
                'group' => 'security',
                'key' => 'captcha_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable CAPTCHA on forms',
            ],
            [
                'group' => 'security',
                'key' => 'captcha_site_key',
                'value' => '6LdTm6IqAAAAALBd0fv-5X_q1KYQyQP0ctKZ59zl',
                'type' => 'string',
                'description' => 'reCAPTCHA site key',
            ],
            [
                'group' => 'security',
                'key' => 'captcha_secret_key',
                'value' => '6LdTm6IqAAAAAL8ncsZDrRkkypeAPaTzpUP7gaoT',
                'type' => 'string',
                'description' => 'reCAPTCHA secret key',
            ],
            [
                'group' => 'security',
                'key' => 'facebook_pixel',
                'value' => null,
                'type' => 'string',
                'description' => 'Facebook Pixel tracking ID',
            ],

            // =====================================================================
            // MAINTENANCE GROUP
            // =====================================================================
            [
                'group' => 'maintenance',
                'key' => 'is_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable maintenance mode',
            ],
            [
                'group' => 'maintenance',
                'key' => 'message',
                'value' => null,
                'type' => 'string',
                'description' => 'Maintenance mode message',
            ],

            // =====================================================================
            // MERCHANT GROUP
            // =====================================================================
            [
                'group' => 'merchant',
                'key' => 'registration_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Allow merchant registration',
            ],

            // =====================================================================
            // DEBUG GROUP
            // =====================================================================
            [
                'group' => 'debug',
                'key' => 'is_enabled',
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable debug mode',
            ],

            // =====================================================================
            // DISPLAY GROUP
            // =====================================================================
            [
                'group' => 'display',
                'key' => 'page_count',
                'value' => 12,
                'type' => 'integer',
                'description' => 'Items per page in listings',
            ],
            [
                'group' => 'display',
                'key' => 'favorite_count',
                'value' => 12,
                'type' => 'integer',
                'description' => 'Favorites per page',
            ],
            [
                'group' => 'display',
                'key' => 'error_banner_404',
                'value' => null,
                'type' => 'file',
                'description' => '404 error page banner',
            ],
            [
                'group' => 'display',
                'key' => 'error_banner_500',
                'value' => null,
                'type' => 'file',
                'description' => '500 error page banner',
            ],
            [
                'group' => 'display',
                'key' => 'popup_background',
                'value' => null,
                'type' => 'file',
                'description' => 'Popup background image',
            ],
            [
                'group' => 'display',
                'key' => 'admin_loader_enabled',
                'value' => true,
                'type' => 'boolean',
                'description' => 'Show loader in admin panel',
            ],
        ];
    }
};

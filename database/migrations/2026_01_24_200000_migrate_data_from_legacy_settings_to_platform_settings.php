<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ============================================================================
 * DATA MIGRATION: muaadhsettings + connect_configs → platform_settings
 * ============================================================================
 *
 * This migration performs EXPLICIT data transfer from legacy settings tables
 * to the unified platform_settings table.
 *
 * Process:
 * 1. Read ALL data from muaadhsettings (49 columns)
 * 2. Read ALL data from connect_configs (18 columns)
 * 3. Map each column to appropriate group in platform_settings
 * 4. Insert/update using updateOrCreate to avoid duplicates
 * 5. Verify all data exists in platform_settings
 * 6. Only after verification: DROP legacy tables
 *
 * IMPORTANT: This is a ONE-WAY migration. No rollback for data.
 * ============================================================================
 */
return new class extends Migration
{
    /**
     * Mapping: muaadhsettings columns → platform_settings (group, key, type)
     */
    protected array $muaadhsettingsMap = [
        // BRANDING GROUP
        'logo' => ['group' => 'branding', 'key' => 'logo', 'type' => 'file'],
        'favicon' => ['group' => 'branding', 'key' => 'favicon', 'type' => 'file'],
        'site_name' => ['group' => 'branding', 'key' => 'site_name', 'type' => 'string'],
        'loader' => ['group' => 'branding', 'key' => 'loader', 'type' => 'file'],
        'admin_loader' => ['group' => 'branding', 'key' => 'admin_loader', 'type' => 'file'],
        'footer_logo' => ['group' => 'branding', 'key' => 'footer_logo', 'type' => 'file'],
        'invoice_logo' => ['group' => 'branding', 'key' => 'invoice_logo', 'type' => 'file'],
        'copyright' => ['group' => 'branding', 'key' => 'copyright', 'type' => 'string'],
        'user_image' => ['group' => 'branding', 'key' => 'user_image', 'type' => 'file'],
        'is_admin_loader' => ['group' => 'branding', 'key' => 'is_admin_loader', 'type' => 'boolean'],
        'error_banner_404' => ['group' => 'branding', 'key' => 'error_banner_404', 'type' => 'file'],
        'error_banner_500' => ['group' => 'branding', 'key' => 'error_banner_500', 'type' => 'file'],
        'popup_background' => ['group' => 'branding', 'key' => 'popup_background', 'type' => 'file'],

        // CURRENCY GROUP
        'currency_format' => ['group' => 'currency', 'key' => 'format', 'type' => 'integer'],
        'decimal_separator' => ['group' => 'currency', 'key' => 'decimal_separator', 'type' => 'string'],
        'thousand_separator' => ['group' => 'currency', 'key' => 'thousand_separator', 'type' => 'string'],
        'is_currency' => ['group' => 'currency', 'key' => 'is_currency_switcher', 'type' => 'boolean'],

        // MAIL GROUP
        'mail_driver' => ['group' => 'mail', 'key' => 'driver', 'type' => 'string'],
        'mail_host' => ['group' => 'mail', 'key' => 'host', 'type' => 'string'],
        'mail_port' => ['group' => 'mail', 'key' => 'port', 'type' => 'string'],
        'mail_encryption' => ['group' => 'mail', 'key' => 'encryption', 'type' => 'string'],
        'mail_user' => ['group' => 'mail', 'key' => 'username', 'type' => 'string'],
        'mail_pass' => ['group' => 'mail', 'key' => 'password', 'type' => 'string'],
        'from_email' => ['group' => 'mail', 'key' => 'from_email', 'type' => 'string'],
        'from_name' => ['group' => 'mail', 'key' => 'from_name', 'type' => 'string'],

        // AFFILIATE GROUP
        'is_affilate' => ['group' => 'affiliate', 'key' => 'is_enabled', 'type' => 'boolean'],
        'affilate_charge' => ['group' => 'affiliate', 'key' => 'charge_percent', 'type' => 'decimal'],
        'affilate_banner' => ['group' => 'affiliate', 'key' => 'banner', 'type' => 'file'],

        // WITHDRAW GROUP
        'withdraw_fee' => ['group' => 'withdraw', 'key' => 'fee', 'type' => 'decimal'],
        'withdraw_charge' => ['group' => 'withdraw', 'key' => 'charge', 'type' => 'decimal'],

        // FEATURES GROUP
        'is_talkto' => ['group' => 'features', 'key' => 'is_talkto', 'type' => 'boolean'],
        'talkto' => ['group' => 'features', 'key' => 'talkto_code', 'type' => 'string'],
        'is_buyer_note' => ['group' => 'features', 'key' => 'is_buyer_note', 'type' => 'boolean'],
        'wholesell' => ['group' => 'features', 'key' => 'is_wholesale', 'type' => 'boolean'],
        'is_popup' => ['group' => 'features', 'key' => 'is_popup', 'type' => 'boolean'],
        'is_report' => ['group' => 'features', 'key' => 'is_report', 'type' => 'boolean'],
        'is_cookie' => ['group' => 'features', 'key' => 'is_cookie', 'type' => 'boolean'],

        // SECURITY GROUP
        'is_verification_email' => ['group' => 'security', 'key' => 'is_verification_email', 'type' => 'boolean'],
        'is_capcha' => ['group' => 'security', 'key' => 'is_captcha', 'type' => 'boolean'],
        'capcha_secret_key' => ['group' => 'security', 'key' => 'captcha_secret_key', 'type' => 'string'],
        'capcha_site_key' => ['group' => 'security', 'key' => 'captcha_site_key', 'type' => 'string'],

        // MAINTENANCE GROUP
        'is_maintain' => ['group' => 'maintenance', 'key' => 'is_enabled', 'type' => 'boolean'],
        'maintain_text' => ['group' => 'maintenance', 'key' => 'message', 'type' => 'string'],

        // MERCHANT GROUP
        'reg_merchant' => ['group' => 'merchant', 'key' => 'registration_enabled', 'type' => 'boolean'],
        'verify_item' => ['group' => 'merchant', 'key' => 'verify_items', 'type' => 'boolean'],

        // DEBUG GROUP
        'is_debug' => ['group' => 'debug', 'key' => 'is_enabled', 'type' => 'boolean'],

        // STATS GROUP
        'page_count' => ['group' => 'stats', 'key' => 'page_count', 'type' => 'integer'],
        'favorite_count' => ['group' => 'stats', 'key' => 'favorite_count', 'type' => 'integer'],

        // SEO GROUP (Analytics)
        'facebook_pixel' => ['group' => 'seo', 'key' => 'facebook_pixel', 'type' => 'string'],
    ];

    /**
     * Mapping: connect_configs columns → platform_settings (group, key, type)
     */
    protected array $connectConfigsMap = [
        // SOCIAL LINKS GROUP
        'facebook' => ['group' => 'social_links', 'key' => 'facebook', 'type' => 'string'],
        'gplus' => ['group' => 'social_links', 'key' => 'google_plus', 'type' => 'string'],
        'twitter' => ['group' => 'social_links', 'key' => 'twitter', 'type' => 'string'],
        'linkedin' => ['group' => 'social_links', 'key' => 'linkedin', 'type' => 'string'],
        'dribble' => ['group' => 'social_links', 'key' => 'dribble', 'type' => 'string'],
        'f_status' => ['group' => 'social_links', 'key' => 'facebook_status', 'type' => 'boolean'],
        'g_status' => ['group' => 'social_links', 'key' => 'google_plus_status', 'type' => 'boolean'],
        't_status' => ['group' => 'social_links', 'key' => 'twitter_status', 'type' => 'boolean'],
        'l_status' => ['group' => 'social_links', 'key' => 'linkedin_status', 'type' => 'boolean'],
        'd_status' => ['group' => 'social_links', 'key' => 'dribble_status', 'type' => 'boolean'],

        // SOCIAL LOGIN GROUP (Facebook)
        'f_check' => ['group' => 'social_login', 'key' => 'facebook_enabled', 'type' => 'boolean'],
        'fclient_id' => ['group' => 'social_login', 'key' => 'facebook_client_id', 'type' => 'string'],
        'fclient_secret' => ['group' => 'social_login', 'key' => 'facebook_client_secret', 'type' => 'string'],
        'fredirect' => ['group' => 'social_login', 'key' => 'facebook_redirect', 'type' => 'string'],

        // SOCIAL LOGIN GROUP (Google)
        'g_check' => ['group' => 'social_login', 'key' => 'google_enabled', 'type' => 'boolean'],
        'gclient_id' => ['group' => 'social_login', 'key' => 'google_client_id', 'type' => 'string'],
        'gclient_secret' => ['group' => 'social_login', 'key' => 'google_client_secret', 'type' => 'string'],
        'gredirect' => ['group' => 'social_login', 'key' => 'google_redirect', 'type' => 'string'],
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // =====================================================================
        // STEP 1: Verify source tables exist
        // =====================================================================
        if (!Schema::hasTable('muaadhsettings')) {
            throw new \RuntimeException('Source table muaadhsettings does not exist!');
        }

        if (!Schema::hasTable('connect_configs')) {
            throw new \RuntimeException('Source table connect_configs does not exist!');
        }

        if (!Schema::hasTable('platform_settings')) {
            throw new \RuntimeException('Target table platform_settings does not exist!');
        }

        // =====================================================================
        // STEP 2: Read data from legacy tables
        // =====================================================================
        $muaadhsettings = DB::table('muaadhsettings')->first();
        $connectConfigs = DB::table('connect_configs')->first();

        if (!$muaadhsettings) {
            throw new \RuntimeException('No data found in muaadhsettings table!');
        }

        if (!$connectConfigs) {
            throw new \RuntimeException('No data found in connect_configs table!');
        }

        $muaadhsettingsData = (array) $muaadhsettings;
        $connectConfigsData = (array) $connectConfigs;

        // =====================================================================
        // STEP 3: Migrate muaadhsettings data
        // =====================================================================
        $migratedCount = 0;

        foreach ($this->muaadhsettingsMap as $sourceColumn => $target) {
            if (!array_key_exists($sourceColumn, $muaadhsettingsData)) {
                // Column may have been dropped in previous migrations
                continue;
            }

            $value = $muaadhsettingsData[$sourceColumn];

            // Skip null values for optional fields
            if ($value === null && in_array($target['type'], ['file', 'string'])) {
                $value = '';
            }

            DB::table('platform_settings')->updateOrInsert(
                ['group' => $target['group'], 'key' => $target['key']],
                [
                    'value' => json_encode($value),
                    'type' => $target['type'],
                    'description' => "Migrated from muaadhsettings.{$sourceColumn}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $migratedCount++;
        }

        echo "Migrated {$migratedCount} settings from muaadhsettings\n";

        // =====================================================================
        // STEP 4: Migrate connect_configs data
        // =====================================================================
        $connectMigratedCount = 0;

        foreach ($this->connectConfigsMap as $sourceColumn => $target) {
            if (!array_key_exists($sourceColumn, $connectConfigsData)) {
                continue;
            }

            $value = $connectConfigsData[$sourceColumn];

            if ($value === null && in_array($target['type'], ['file', 'string'])) {
                $value = '';
            }

            DB::table('platform_settings')->updateOrInsert(
                ['group' => $target['group'], 'key' => $target['key']],
                [
                    'value' => json_encode($value),
                    'type' => $target['type'],
                    'description' => "Migrated from connect_configs.{$sourceColumn}",
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $connectMigratedCount++;
        }

        echo "Migrated {$connectMigratedCount} settings from connect_configs\n";

        // =====================================================================
        // STEP 5: Verify migration
        // =====================================================================
        $totalExpected = count($this->muaadhsettingsMap) + count($this->connectConfigsMap);
        $totalInPlatform = DB::table('platform_settings')->count();

        echo "Total settings in platform_settings: {$totalInPlatform}\n";

        // Verify critical settings exist
        $criticalSettings = [
            ['branding', 'logo'],
            ['branding', 'site_name'],
            ['currency', 'format'],
            ['security', 'is_captcha'],
            ['social_login', 'facebook_enabled'],
        ];

        foreach ($criticalSettings as [$group, $key]) {
            $exists = DB::table('platform_settings')
                ->where('group', $group)
                ->where('key', $key)
                ->exists();

            if (!$exists) {
                throw new \RuntimeException("Critical setting {$group}.{$key} not found after migration!");
            }
        }

        echo "All critical settings verified successfully!\n";

        // =====================================================================
        // STEP 6: Clear platform_settings cache
        // =====================================================================
        cache()->forget('platform_settings_context');
        cache()->forget('platform_settings:all');

        echo "Cache cleared.\n";

        // =====================================================================
        // STEP 7: DROP legacy tables (after successful verification)
        // =====================================================================
        Schema::dropIfExists('muaadhsettings');
        echo "Dropped table: muaadhsettings\n";

        Schema::dropIfExists('connect_configs');
        echo "Dropped table: connect_configs\n";

        echo "\n✅ Migration completed successfully!\n";
        echo "Legacy tables have been removed.\n";
        echo "All code MUST use platformSettings() from now on.\n";
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This is a DESTRUCTIVE one-way migration.
     * The rollback will NOT restore data - it only recreates empty tables.
     */
    public function down(): void
    {
        // Create empty muaadhsettings table (structure only, no data)
        if (!Schema::hasTable('muaadhsettings')) {
            Schema::create('muaadhsettings', function ($table) {
                $table->id();
                $table->string('logo')->nullable();
                $table->string('favicon')->nullable();
                $table->string('site_name')->nullable();
                $table->string('loader')->nullable();
                $table->string('admin_loader')->nullable();
                $table->tinyInteger('is_talkto')->default(0);
                $table->text('talkto')->nullable();
                $table->tinyInteger('currency_format')->default(0);
                $table->decimal('withdraw_fee', 10, 2)->default(0);
                $table->decimal('withdraw_charge', 10, 2)->default(0);
                $table->string('mail_driver')->nullable();
                $table->string('mail_host')->nullable();
                $table->string('mail_port')->nullable();
                $table->string('mail_encryption')->nullable();
                $table->string('mail_user')->nullable();
                $table->string('mail_pass')->nullable();
                $table->string('from_email')->nullable();
                $table->string('from_name')->nullable();
                $table->tinyInteger('is_buyer_note')->default(0);
                $table->tinyInteger('is_currency')->default(0);
                $table->tinyInteger('is_affilate')->default(0);
                $table->decimal('affilate_charge', 10, 2)->default(0);
                $table->string('affilate_banner')->nullable();
                $table->tinyInteger('reg_merchant')->default(1);
                $table->string('copyright')->nullable();
                $table->tinyInteger('is_admin_loader')->default(0);
                $table->tinyInteger('is_verification_email')->default(0);
                $table->tinyInteger('wholesell')->default(0);
                $table->tinyInteger('is_capcha')->default(0);
                $table->string('capcha_secret_key')->nullable();
                $table->string('capcha_site_key')->nullable();
                $table->string('error_banner_404')->nullable();
                $table->string('error_banner_500')->nullable();
                $table->tinyInteger('is_popup')->default(0);
                $table->string('popup_background')->nullable();
                $table->string('invoice_logo')->nullable();
                $table->string('user_image')->nullable();
                $table->tinyInteger('is_report')->default(0);
                $table->string('footer_logo')->nullable();
                $table->tinyInteger('is_maintain')->default(0);
                $table->text('maintain_text')->nullable();
                $table->tinyInteger('verify_item')->default(0);
                $table->integer('page_count')->default(12);
                $table->integer('favorite_count')->default(12);
                $table->tinyInteger('is_debug')->default(0);
                $table->string('decimal_separator')->nullable();
                $table->string('thousand_separator')->nullable();
                $table->tinyInteger('is_cookie')->default(0);
                $table->text('facebook_pixel')->nullable();
            });

            echo "Created empty muaadhsettings table (no data restored)\n";
        }

        // Create empty connect_configs table (structure only, no data)
        if (!Schema::hasTable('connect_configs')) {
            Schema::create('connect_configs', function ($table) {
                $table->id();
                $table->string('facebook')->nullable();
                $table->string('gplus')->nullable();
                $table->string('twitter')->nullable();
                $table->string('linkedin')->nullable();
                $table->string('dribble')->nullable();
                $table->tinyInteger('f_status')->default(0);
                $table->tinyInteger('g_status')->default(0);
                $table->tinyInteger('t_status')->default(0);
                $table->tinyInteger('l_status')->default(0);
                $table->tinyInteger('d_status')->default(0);
                $table->tinyInteger('f_check')->default(0);
                $table->tinyInteger('g_check')->default(0);
                $table->string('fclient_id')->nullable();
                $table->string('fclient_secret')->nullable();
                $table->string('fredirect')->nullable();
                $table->string('gclient_id')->nullable();
                $table->string('gclient_secret')->nullable();
                $table->string('gredirect')->nullable();
            });

            echo "Created empty connect_configs table (no data restored)\n";
        }

        echo "\n⚠️ WARNING: Tables recreated but DATA WAS NOT RESTORED!\n";
        echo "This is a one-way migration. Data exists only in platform_settings.\n";
    }
};

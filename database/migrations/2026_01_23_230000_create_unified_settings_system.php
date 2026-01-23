<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * ============================================================================
 * UNIFIED SETTINGS SYSTEM - ARCHITECTURAL MIGRATION
 * ============================================================================
 *
 * This migration creates the new unified settings architecture:
 *
 * 1. platform_settings - All platform-wide settings (JSON-based)
 * 2. merchant_settings - Per-merchant settings (JSON-based)
 * 3. pages - Policy pages only (terms, privacy, refund)
 *
 * REPLACES (will be dropped in future migration):
 * - muaadhsettings
 * - settings
 * - seotools
 * - home_page_themes
 * - typefaces
 * - connect_configs
 * - languages
 * - frontend_settings (already dropped)
 * - announcements
 * - testimonials
 * - publications
 * - static_content
 * - help_articles
 * - article_types
 * - mailing_list
 *
 * ============================================================================
 */
return new class extends Migration
{
    public function up(): void
    {
        // ====================================================================
        // 1. PLATFORM SETTINGS - Single source for all platform config
        // ====================================================================
        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->index()->comment('Setting group: branding, mail, payment, etc');
            $table->string('key', 100)->comment('Setting key within group');
            $table->json('value')->nullable()->comment('Setting value (JSON for flexibility)');
            $table->string('type', 20)->default('string')->comment('Value type: string, boolean, integer, json, file');
            $table->text('description')->nullable()->comment('Human-readable description');
            $table->timestamps();

            $table->unique(['group', 'key'], 'platform_settings_group_key_unique');
        });

        // ====================================================================
        // 2. MERCHANT SETTINGS - Per-merchant configuration
        // ====================================================================
        Schema::create('merchant_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('merchant_id')->comment('FK to users.id (merchant)');
            $table->string('group', 50)->index()->comment('Setting group');
            $table->string('key', 100)->comment('Setting key within group');
            $table->json('value')->nullable()->comment('Setting value (JSON)');
            $table->string('type', 20)->default('string');
            $table->timestamps();

            $table->unique(['merchant_id', 'group', 'key'], 'merchant_settings_unique');
            $table->index(['merchant_id', 'group'], 'merchant_settings_merchant_group');

            $table->foreign('merchant_id')
                ->references('id')->on('users')
                ->onDelete('cascade');
        });

        // ====================================================================
        // 3. PAGES - Policy pages only (terms, privacy, refund)
        // ====================================================================
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 50)->unique()->comment('URL slug: terms, privacy, refund');
            $table->string('title')->comment('Page title');
            $table->string('title_ar')->nullable()->comment('Arabic title');
            $table->longText('content')->nullable()->comment('Page content (HTML)');
            $table->longText('content_ar')->nullable()->comment('Arabic content');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ====================================================================
        // 4. MIGRATE DATA FROM OLD TABLES
        // ====================================================================
        $this->migrateFromMuaadhsettings();
        $this->migrateFromSettings();
        $this->migrateFromSeotools();
        $this->migrateFromConnectConfigs();
        $this->migrateFromLanguages();
        $this->migrateFromStaticContent();
    }

    /**
     * Migrate muaadhsettings to platform_settings
     */
    private function migrateFromMuaadhsettings(): void
    {
        if (!Schema::hasTable('muaadhsettings')) {
            return;
        }

        $old = DB::table('muaadhsettings')->first();
        if (!$old) {
            return;
        }

        $settings = [
            // Branding group
            ['branding', 'logo', $old->logo ?? null, 'file'],
            ['branding', 'favicon', $old->favicon ?? null, 'file'],
            ['branding', 'site_name', $old->site_name ?? null, 'string'],
            ['branding', 'footer_logo', $old->footer_logo ?? null, 'file'],
            ['branding', 'invoice_logo', $old->invoice_logo ?? null, 'file'],
            ['branding', 'copyright', $old->copyright ?? null, 'string'],
            ['branding', 'loader', $old->loader ?? null, 'file'],
            ['branding', 'admin_loader', $old->admin_loader ?? null, 'file'],
            ['branding', 'is_admin_loader', $old->is_admin_loader ?? 0, 'boolean'],
            ['branding', 'user_image', $old->user_image ?? null, 'file'],
            ['branding', 'error_banner_404', $old->error_banner_404 ?? null, 'file'],
            ['branding', 'error_banner_500', $old->error_banner_500 ?? null, 'file'],

            // Mail group
            ['mail', 'driver', $old->mail_driver ?? null, 'string'],
            ['mail', 'host', $old->mail_host ?? null, 'string'],
            ['mail', 'port', $old->mail_port ?? null, 'string'],
            ['mail', 'encryption', $old->mail_encryption ?? null, 'string'],
            ['mail', 'username', $old->mail_user ?? null, 'string'],
            ['mail', 'password', $old->mail_pass ?? null, 'string'],
            ['mail', 'from_email', $old->from_email ?? null, 'string'],
            ['mail', 'from_name', $old->from_name ?? null, 'string'],

            // Currency group
            ['currency', 'format', $old->currency_format ?? 0, 'integer'],
            ['currency', 'decimal_separator', $old->decimal_separator ?? '.', 'string'],
            ['currency', 'thousand_separator', $old->thousand_separator ?? ',', 'string'],
            ['currency', 'is_currency_switcher', $old->is_currency ?? 1, 'boolean'],

            // Affiliate group
            ['affiliate', 'is_enabled', $old->is_affilate ?? 0, 'boolean'],
            ['affiliate', 'charge_percent', $old->affilate_charge ?? 0, 'integer'],
            ['affiliate', 'banner', $old->affilate_banner ?? null, 'file'],

            // Withdraw group
            ['withdraw', 'fee', $old->withdraw_fee ?? 0, 'decimal'],
            ['withdraw', 'charge', $old->withdraw_charge ?? 0, 'decimal'],

            // Features group
            ['features', 'is_talkto', $old->is_talkto ?? 0, 'boolean'],
            ['features', 'talkto_code', $old->talkto ?? null, 'string'],
            ['features', 'is_buyer_note', $old->is_buyer_note ?? 1, 'boolean'],
            ['features', 'is_popup', $old->is_popup ?? 0, 'boolean'],
            ['features', 'popup_background', $old->popup_background ?? null, 'file'],
            ['features', 'is_report', $old->is_report ?? 0, 'boolean'],
            ['features', 'is_cookie', $old->is_cookie ?? 0, 'boolean'],
            ['features', 'facebook_pixel', $old->facebook_pixel ?? null, 'string'],

            // Security group
            ['security', 'is_captcha', $old->is_capcha ?? 0, 'boolean'],
            ['security', 'captcha_site_key', $old->capcha_site_key ?? null, 'string'],
            ['security', 'captcha_secret_key', $old->capcha_secret_key ?? null, 'string'],
            ['security', 'is_verification_email', $old->is_verification_email ?? 0, 'boolean'],

            // Maintenance group
            ['maintenance', 'is_enabled', $old->is_maintain ?? 0, 'boolean'],
            ['maintenance', 'text', $old->maintain_text ?? null, 'string'],

            // Merchant group
            ['merchant', 'registration_enabled', $old->reg_merchant ?? 0, 'boolean'],
            ['merchant', 'verify_items', $old->verify_item ?? 0, 'boolean'],

            // Debug group
            ['debug', 'is_enabled', $old->is_debug ?? 0, 'boolean'],

            // Counters (read-only, for reference)
            ['stats', 'page_count', $old->page_count ?? 0, 'integer'],
            ['stats', 'favorite_count', $old->favorite_count ?? 0, 'integer'],
        ];

        foreach ($settings as [$group, $key, $value, $type]) {
            if ($value !== null) {
                DB::table('platform_settings')->insert([
                    'group' => $group,
                    'key' => $key,
                    'value' => json_encode($value),
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Migrate settings to platform_settings
     */
    private function migrateFromSettings(): void
    {
        if (!Schema::hasTable('settings')) {
            return;
        }

        $old = DB::table('settings')->first();
        if (!$old) {
            return;
        }

        $settings = [
            ['contact', 'email', $old->email ?? null, 'string'],
            ['contact', 'phone', $old->phone ?? null, 'string'],
            ['contact', 'address', $old->address ?? null, 'string'],
            ['branding', 'site_url', $old->site_url ?? null, 'string'],
        ];

        foreach ($settings as [$group, $key, $value, $type]) {
            if ($value !== null) {
                // Check if exists (from muaadhsettings)
                $exists = DB::table('platform_settings')
                    ->where('group', $group)
                    ->where('key', $key)
                    ->exists();

                if (!$exists) {
                    DB::table('platform_settings')->insert([
                        'group' => $group,
                        'key' => $key,
                        'value' => json_encode($value),
                        'type' => $type,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    /**
     * Migrate seotools to platform_settings
     */
    private function migrateFromSeotools(): void
    {
        if (!Schema::hasTable('seotools')) {
            return;
        }

        $old = DB::table('seotools')->first();
        if (!$old) {
            return;
        }

        $fields = ['google_analytics', 'meta_keywords', 'meta_description'];
        foreach ($fields as $field) {
            if (!empty($old->$field)) {
                DB::table('platform_settings')->insert([
                    'group' => 'seo',
                    'key' => $field,
                    'value' => json_encode($old->$field),
                    'type' => 'string',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Migrate connect_configs to platform_settings
     */
    private function migrateFromConnectConfigs(): void
    {
        if (!Schema::hasTable('connect_configs')) {
            return;
        }

        $configs = DB::table('connect_configs')->get();
        foreach ($configs as $config) {
            // Store each social login config
            DB::table('platform_settings')->insert([
                'group' => 'social_login',
                'key' => $config->name ?? 'unknown',
                'value' => json_encode([
                    'client_id' => $config->client_id ?? null,
                    'client_secret' => $config->client_secret ?? null,
                    'status' => $config->status ?? 0,
                ]),
                'type' => 'json',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Migrate languages to platform_settings
     */
    private function migrateFromLanguages(): void
    {
        if (!Schema::hasTable('languages')) {
            return;
        }

        $languages = DB::table('languages')->get();
        $languagesArray = [];

        foreach ($languages as $lang) {
            $languagesArray[] = [
                'id' => $lang->id,
                'name' => $lang->name ?? null,
                'code' => $lang->language ?? null,
                'rtl' => $lang->rtl ?? 0,
                'is_default' => $lang->is_default ?? 0,
            ];
        }

        if (!empty($languagesArray)) {
            DB::table('platform_settings')->insert([
                'group' => 'localization',
                'key' => 'languages',
                'value' => json_encode($languagesArray),
                'type' => 'json',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Migrate static_content to pages (policies only)
     */
    private function migrateFromStaticContent(): void
    {
        if (!Schema::hasTable('static_content')) {
            return;
        }

        // Only migrate policy pages
        $policyPages = [
            'terms' => 'Terms & Conditions',
            'privacy' => 'Privacy Policy',
            'refund' => 'Refund Policy',
        ];

        foreach ($policyPages as $slug => $title) {
            $content = DB::table('static_content')
                ->where('slug', $slug)
                ->orWhere('name', 'like', "%{$title}%")
                ->orWhere('name', 'like', "%{$slug}%")
                ->first();

            if ($content) {
                DB::table('pages')->insert([
                    'slug' => $slug,
                    'title' => $content->name ?? $title,
                    'title_ar' => $content->name_ar ?? null,
                    'content' => $content->details ?? null,
                    'content_ar' => $content->details_ar ?? null,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } else {
                // Create empty page
                DB::table('pages')->insert([
                    'slug' => $slug,
                    'title' => $title,
                    'content' => null,
                    'is_active' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
        Schema::dropIfExists('merchant_settings');
        Schema::dropIfExists('platform_settings');
    }
};

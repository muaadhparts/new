<?php

namespace App\Services;

use App\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================================
 * PLATFORM SETTINGS SERVICE
 * ============================================================================
 *
 * Unified service for accessing platform settings.
 * Provides backward compatibility with old $gs (Muaadhsetting) patterns.
 *
 * Usage in Controllers/Services:
 * ------------------------------
 * $settings = app(PlatformSettingsService::class);
 * $logo = $settings->logo;
 * $siteName = $settings->site_name;
 *
 * Usage in Views (via helper):
 * ----------------------------
 * {{ platformSetting('branding', 'logo') }}
 * {{ $gs->logo }} // backward compatible via GlobalData service
 *
 * ============================================================================
 */
class PlatformSettingsService
{
    /**
     * Cached settings object
     */
    protected ?object $settings = null;

    /**
     * Get all settings as object (backward compatible with $gs)
     *
     * @return object
     */
    public function all(): object
    {
        if ($this->settings === null) {
            $this->settings = $this->loadSettings();
        }

        return $this->settings;
    }

    /**
     * Magic getter for backward compatibility
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Get a setting by key (searches all groups)
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $settings = $this->all();

        return $settings->$key ?? $default;
    }

    /**
     * Get setting from specific group
     *
     * @param string $group
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getFromGroup(string $group, string $key, $default = null)
    {
        return PlatformSetting::get($group, $key, $default);
    }

    /**
     * Load all settings into a flat object
     * Maps to old Muaadhsetting field names for backward compatibility
     */
    protected function loadSettings(): object
    {
        $cacheKey = 'platform_settings_service_all';

        return Cache::remember($cacheKey, 3600, function () {
            $allSettings = PlatformSetting::getAll();

            // Build flat object with backward-compatible field names
            $flat = [];

            // Branding
            $branding = $allSettings['branding'] ?? [];
            $flat['logo'] = $branding['logo'] ?? null;
            $flat['favicon'] = $branding['favicon'] ?? null;
            $flat['site_name'] = $branding['site_name'] ?? '';
            $flat['footer_logo'] = $branding['footer_logo'] ?? null;
            $flat['invoice_logo'] = $branding['invoice_logo'] ?? null;
            $flat['copyright'] = $branding['copyright'] ?? '';
            $flat['loader'] = $branding['loader'] ?? null;
            $flat['admin_loader'] = $branding['admin_loader'] ?? null;
            $flat['is_admin_loader'] = $branding['is_admin_loader'] ?? 0;
            $flat['user_image'] = $branding['user_image'] ?? null;
            $flat['error_banner_404'] = $branding['error_banner_404'] ?? null;
            $flat['error_banner_500'] = $branding['error_banner_500'] ?? null;
            $flat['site_url'] = $branding['site_url'] ?? null;

            // Mail
            $mail = $allSettings['mail'] ?? [];
            $flat['mail_driver'] = $mail['driver'] ?? null;
            $flat['mail_host'] = $mail['host'] ?? null;
            $flat['mail_port'] = $mail['port'] ?? null;
            $flat['mail_encryption'] = $mail['encryption'] ?? null;
            $flat['mail_user'] = $mail['username'] ?? null;
            $flat['mail_pass'] = $mail['password'] ?? null;
            $flat['from_email'] = $mail['from_email'] ?? null;
            $flat['from_name'] = $mail['from_name'] ?? null;

            // Currency
            $currency = $allSettings['currency'] ?? [];
            $flat['currency_format'] = $currency['format'] ?? 0;
            $flat['decimal_separator'] = $currency['decimal_separator'] ?? '.';
            $flat['thousand_separator'] = $currency['thousand_separator'] ?? ',';
            $flat['is_currency'] = $currency['is_currency_switcher'] ?? 1;

            // Affiliate
            $affiliate = $allSettings['affiliate'] ?? [];
            $flat['is_affilate'] = $affiliate['is_enabled'] ?? 0;
            $flat['affilate_charge'] = $affiliate['charge_percent'] ?? 0;
            $flat['affilate_banner'] = $affiliate['banner'] ?? null;

            // Withdraw
            $withdraw = $allSettings['withdraw'] ?? [];
            $flat['withdraw_fee'] = $withdraw['fee'] ?? 0;
            $flat['withdraw_charge'] = $withdraw['charge'] ?? 0;

            // Features
            $features = $allSettings['features'] ?? [];
            $flat['is_talkto'] = $features['is_talkto'] ?? 0;
            $flat['talkto'] = $features['talkto_code'] ?? null;
            $flat['is_buyer_note'] = $features['is_buyer_note'] ?? 1;
            $flat['is_popup'] = $features['is_popup'] ?? 0;
            $flat['popup_background'] = $features['popup_background'] ?? null;
            $flat['is_report'] = $features['is_report'] ?? 0;
            $flat['is_cookie'] = $features['is_cookie'] ?? 0;
            $flat['facebook_pixel'] = $features['facebook_pixel'] ?? null;

            // Security
            $security = $allSettings['security'] ?? [];
            $flat['is_capcha'] = $security['is_captcha'] ?? 0;
            $flat['capcha_site_key'] = $security['captcha_site_key'] ?? null;
            $flat['capcha_secret_key'] = $security['captcha_secret_key'] ?? null;
            $flat['is_verification_email'] = $security['is_verification_email'] ?? 0;

            // Maintenance
            $maintenance = $allSettings['maintenance'] ?? [];
            $flat['is_maintain'] = $maintenance['is_enabled'] ?? 0;
            $flat['maintain_text'] = $maintenance['text'] ?? null;

            // Merchant
            $merchant = $allSettings['merchant'] ?? [];
            $flat['reg_merchant'] = $merchant['registration_enabled'] ?? 0;
            $flat['verify_item'] = $merchant['verify_items'] ?? 0;

            // Debug
            $debug = $allSettings['debug'] ?? [];
            $flat['is_debug'] = $debug['is_enabled'] ?? 0;

            // Contact
            $contact = $allSettings['contact'] ?? [];
            $flat['email'] = $contact['email'] ?? null;
            $flat['phone'] = $contact['phone'] ?? null;
            $flat['address'] = $contact['address'] ?? null;

            // SEO
            $seo = $allSettings['seo'] ?? [];
            $flat['google_analytics'] = $seo['google_analytics'] ?? null;
            $flat['meta_keywords'] = $seo['meta_keywords'] ?? null;
            $flat['meta_description'] = $seo['meta_description'] ?? null;

            // Stats
            $stats = $allSettings['stats'] ?? [];
            $flat['page_count'] = $stats['page_count'] ?? 0;
            $flat['favorite_count'] = $stats['favorite_count'] ?? 0;

            return (object) $flat;
        });
    }

    /**
     * Clear cached settings
     */
    public function clearCache(): void
    {
        Cache::forget('platform_settings_service_all');
        PlatformSetting::clearCache();
        $this->settings = null;
    }
}

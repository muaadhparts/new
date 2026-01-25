<?php

namespace App\Domain\Platform\Services;

use App\Domain\Platform\Models\PlatformSetting;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================================
 * THEME SERVICE
 * ============================================================================
 *
 * Dedicated service for theme management.
 * Handles all theme-related operations: reading, writing, presets, CSS generation.
 *
 * Usage:
 * ------
 * $theme = app(ThemeService::class);
 * $primary = $theme->get('theme_primary');
 * $theme->set('theme_primary', '#006c35');
 * $theme->applyPreset('saudi');
 * $theme->generateCss();
 *
 * ============================================================================
 */
class ThemeService
{
    /**
     * Cache key for theme settings
     */
    protected const CACHE_KEY = 'theme_service_settings';

    /**
     * Cache duration in seconds (1 hour)
     */
    protected const CACHE_TTL = 3600;

    /**
     * Default theme values (used when no value exists)
     */
    protected array $defaults = [
        // PRIMARY COLORS
        'theme_primary' => '#c3002f',
        'theme_primary_hover' => '#a00025',
        'theme_primary_dark' => '#8a0020',
        'theme_primary_light' => '#fef2f4',

        // SECONDARY COLORS
        'theme_secondary' => '#1f0300',
        'theme_secondary_hover' => '#351c1a',
        'theme_secondary_light' => '#4c3533',

        // TEXT COLORS
        'theme_text_primary' => '#1f0300',
        'theme_text_secondary' => '#4c3533',
        'theme_text_muted' => '#796866',
        'theme_text_light' => '#9a8e8c',

        // BACKGROUND COLORS
        'theme_bg_body' => '#ffffff',
        'theme_bg_light' => '#f8f7f7',
        'theme_bg_gray' => '#e9e6e6',
        'theme_bg_dark' => '#030712',

        // STATUS COLORS
        'theme_success' => '#27be69',
        'theme_warning' => '#fac03c',
        'theme_danger' => '#f2415a',
        'theme_info' => '#0ea5e9',

        // BORDER COLORS
        'theme_border' => '#d9d4d4',
        'theme_border_light' => '#e9e6e6',
        'theme_border_dark' => '#c7c0bf',

        // HEADER & FOOTER
        'theme_header_bg' => '#ffffff',
        'theme_header_height' => '80px',
        'theme_header_shadow' => '0 2px 10px rgba(0,0,0,0.1)',
        'theme_header_text' => '#1f0300',
        'theme_footer_bg' => '#030712',
        'theme_footer_text' => '#ffffff',
        'theme_footer_link_hover' => '#c3002f',
        'theme_footer_padding' => '60px',
        'theme_footer_text_muted' => '#d9d4d4',
        'theme_footer_link' => '#ffffff',
        'theme_footer_border' => '#374151',

        // TYPOGRAPHY
        'theme_font_primary' => 'Poppins',
        'theme_font_heading' => 'Saira',
        'theme_font_size_base' => '16px',
        'theme_font_size_sm' => '14px',
        'theme_font_size_lg' => '18px',
        'theme_line_height' => '1.5',

        // NAVIGATION
        'theme_nav_link_color' => '#1f0300',
        'theme_nav_link_hover' => '#c3002f',
        'theme_nav_font_size' => '15px',
        'theme_nav_font_weight' => '500',

        // BORDER RADIUS
        'theme_radius_xs' => '3px',
        'theme_radius_sm' => '4px',
        'theme_radius' => '8px',
        'theme_radius_lg' => '12px',
        'theme_radius_xl' => '16px',
        'theme_radius_pill' => '50px',

        // SHADOWS
        'theme_shadow_xs' => '0 1px 2px rgba(0,0,0,0.04)',
        'theme_shadow_sm' => '0 1px 3px rgba(0,0,0,0.06)',
        'theme_shadow' => '0 2px 8px rgba(0,0,0,0.1)',
        'theme_shadow_lg' => '0 4px 16px rgba(0,0,0,0.15)',
        'theme_shadow_xl' => '0 8px 30px rgba(0,0,0,0.2)',

        // SPACING
        'theme_spacing_xs' => '4px',
        'theme_spacing_sm' => '8px',
        'theme_spacing' => '16px',
        'theme_spacing_lg' => '24px',
        'theme_spacing_xl' => '32px',

        // BUTTONS
        'theme_btn_padding_x' => '24px',
        'theme_btn_padding_y' => '12px',
        'theme_btn_font_size' => '14px',
        'theme_btn_font_weight' => '600',
        'theme_btn_radius' => '8px',
        'theme_btn_shadow' => 'none',

        // CARDS
        'theme_card_bg' => '#ffffff',
        'theme_card_border' => '#e9e6e6',
        'theme_card_radius' => '12px',
        'theme_card_shadow' => '0 2px 8px rgba(0,0,0,0.08)',
        'theme_card_hover_shadow' => '0 4px 16px rgba(0,0,0,0.12)',
        'theme_card_padding' => '20px',

        // ITEM CARDS
        'theme_item_name_size' => '14px',
        'theme_item_name_weight' => '500',
        'theme_item_price_size' => '16px',
        'theme_item_price_weight' => '700',
        'theme_item_card_radius' => '12px',
        'theme_item_img_radius' => '8px',
        'theme_item_hover_scale' => '1.02',

        // MODALS
        'theme_modal_bg' => '#ffffff',
        'theme_modal_radius' => '16px',
        'theme_modal_shadow' => '0 25px 50px rgba(0,0,0,0.25)',
        'theme_modal_backdrop' => 'rgba(0,0,0,0.5)',
        'theme_modal_header_bg' => '#f8f7f7',

        // TABLES
        'theme_table_header_bg' => '#f8f7f7',
        'theme_table_header_text' => '#1f0300',
        'theme_table_border' => '#e9e6e6',
        'theme_table_hover_bg' => '#f8f7f7',
        'theme_table_stripe_bg' => '#fafafa',

        // FORMS/INPUTS
        'theme_input_height' => '48px',
        'theme_input_bg' => '#ffffff',
        'theme_input_border' => '#d9d4d4',
        'theme_input_radius' => '8px',
        'theme_input_focus_border' => '#c3002f',
        'theme_input_focus_shadow' => '0 0 0 3px rgba(195,0,47,0.1)',
        'theme_input_placeholder' => '#9a8e8c',

        // BADGES
        'theme_badge_radius' => '20px',
        'theme_badge_padding' => '4px 12px',
        'theme_badge_font_size' => '12px',
        'theme_badge_font_weight' => '600',

        // CHIPS
        'theme_chip_bg' => '#f8f7f7',
        'theme_chip_text' => '#4c3533',
        'theme_chip_radius' => '6px',
        'theme_chip_border' => '#e9e6e6',

        // SCROLLBAR
        'theme_scrollbar_width' => '10px',
        'theme_scrollbar_track' => '#f1f1f1',
        'theme_scrollbar_thumb' => '#c1c1c1',
        'theme_scrollbar_thumb_hover' => '#a1a1a1',

        // TRANSITIONS
        'theme_transition_fast' => 'all 0.15s ease',
        'theme_transition' => 'all 0.3s ease',
        'theme_transition_slow' => 'all 0.5s ease',

        // SEARCH
        'theme_search_bg' => '#ffffff',
        'theme_search_border' => '#e9e6e6',
        'theme_search_radius' => '50px',
        'theme_search_height' => '50px',
        'theme_search_shadow' => '0 4px 15px rgba(0,0,0,0.08)',

        // CATEGORY
        'theme_category_bg' => '#ffffff',
        'theme_category_radius' => '12px',
        'theme_category_shadow' => '0 2px 8px rgba(0,0,0,0.08)',
        'theme_category_hover_shadow' => '0 8px 25px rgba(0,0,0,0.15)',

        // PAGINATION
        'theme_pagination_size' => '40px',
        'theme_pagination_radius' => '8px',
        'theme_pagination_gap' => '5px',

        // ALERTS
        'theme_alert_radius' => '8px',
        'theme_alert_padding' => '16px 20px',

        // BREADCRUMB
        'theme_breadcrumb_bg' => '#f8f7f7',
        'theme_breadcrumb_separator' => '/',
        'theme_breadcrumb_text' => '#796866',

        // SOCIAL
        'theme_facebook' => '#1877f2',
        'theme_twitter' => '#1da1f2',
        'theme_instagram' => '#e4405f',
        'theme_whatsapp' => '#25d366',
        'theme_youtube' => '#ff0000',
        'theme_linkedin' => '#0a66c2',
    ];

    /**
     * Presets definitions
     */
    protected array $presets = [];

    public function __construct()
    {
        $this->loadPresets();
    }

    // =========================================================================
    // PUBLIC API
    // =========================================================================

    /**
     * Get a theme setting value
     */
    public function get(string $key, $default = null)
    {
        $value = PlatformSetting::get('theme', $key);

        if ($value !== null) {
            return $value;
        }

        return $default ?? ($this->defaults[$key] ?? null);
    }

    /**
     * Set a theme setting value
     */
    public function set(string $key, $value): void
    {
        PlatformSetting::set('theme', $key, $value);
        $this->clearCache();
    }

    /**
     * Set multiple theme settings at once
     */
    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            if ($value !== null && $value !== '') {
                PlatformSetting::set('theme', $key, $value);
            }
        }
        $this->clearCache();
    }

    /**
     * Get all theme settings (merged with defaults)
     */
    public function getAll(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            $settings = [];

            foreach ($this->defaults as $key => $default) {
                $settings[$key] = PlatformSetting::get('theme', $key) ?? $default;
            }

            return $settings;
        });
    }

    /**
     * Get all theme settings as object (for blade compatibility)
     */
    public function getAllAsObject(): object
    {
        return (object) $this->getAll();
    }

    /**
     * Get default values
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * Get available presets
     */
    public function getPresets(): array
    {
        return $this->presets;
    }

    /**
     * Apply a preset
     */
    public function applyPreset(string $presetName): bool
    {
        if (!isset($this->presets[$presetName])) {
            return false;
        }

        $this->setMany($this->presets[$presetName]);
        $this->generateCss();

        return true;
    }

    /**
     * Clear theme cache
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
        Cache::forget('platform_settings_service_all');
        Cache::forget('platform_settings_context');
        PlatformSetting::clearCache();
    }

    // =========================================================================
    // CSS GENERATION
    // =========================================================================

    /**
     * Generate theme CSS file
     */
    public function generateCss(): string
    {
        $cssPath = public_path('assets/front/css/theme-colors.css');
        $css = $this->buildCssContent();

        file_put_contents($cssPath, $css);

        return $cssPath;
    }

    /**
     * Build CSS content from theme settings
     */
    protected function buildCssContent(): string
    {
        $s = $this->getAll(); // All settings

        // Calculate derived colors
        $primaryRgb = $this->hexToRgb($s['theme_primary']);
        $successRgb = $this->hexToRgb($s['theme_success']);
        $warningRgb = $this->hexToRgb($s['theme_warning']);
        $dangerRgb = $this->hexToRgb($s['theme_danger']);
        $infoRgb = $this->hexToRgb($s['theme_info']);

        // Calculate text-on colors (WCAG contrast)
        $textOnPrimary = $this->getContrastTextColor($s['theme_primary']);
        $textOnSecondary = $this->getContrastTextColor($s['theme_secondary']);
        $textOnSuccess = $this->getContrastTextColor($s['theme_success']);
        $textOnWarning = $this->getContrastTextColor($s['theme_warning']);
        $textOnDanger = $this->getContrastTextColor($s['theme_danger']);
        $textOnInfo = $this->getContrastTextColor($s['theme_info']);
        $textOnDark = $this->getContrastTextColor($s['theme_bg_dark']);
        $textOnLight = $this->getContrastTextColor($s['theme_bg_light']);

        // Calculate light/hover variants
        $successLight = $this->lightenColor($s['theme_success'], 0.9);
        $warningLight = $this->lightenColor($s['theme_warning'], 0.9);
        $dangerLight = $this->lightenColor($s['theme_danger'], 0.9);
        $infoLight = $this->lightenColor($s['theme_info'], 0.9);

        $successHover = $this->darkenColor($s['theme_success'], 0.15);
        $warningHover = $this->darkenColor($s['theme_warning'], 0.15);
        $dangerHover = $this->darkenColor($s['theme_danger'], 0.15);
        $infoHover = $this->darkenColor($s['theme_info'], 0.15);

        $timestamp = now()->format('Y-m-d H:i:s');

        return <<<CSS
/**
 * ========================================
 * THEME BUILDER - Generated CSS Variables
 * ========================================
 * Generated by ThemeService
 * Do not edit manually - changes will be overwritten
 * Generated at: {$timestamp}
 */
:root {
    /* ===== PRIMARY BRAND COLORS ===== */
    --theme-primary: {$s['theme_primary']};
    --theme-primary-hover: {$s['theme_primary_hover']};
    --theme-primary-dark: {$s['theme_primary_dark']};
    --theme-primary-light: {$s['theme_primary_light']};
    --theme-primary-rgb: {$primaryRgb};

    /* ===== SECONDARY COLORS ===== */
    --theme-secondary: {$s['theme_secondary']};
    --theme-secondary-hover: {$s['theme_secondary_hover']};
    --theme-secondary-light: {$s['theme_secondary_light']};

    /* ===== TEXT COLORS ===== */
    --theme-text-primary: {$s['theme_text_primary']};
    --theme-text-secondary: {$s['theme_text_secondary']};
    --theme-text-muted: {$s['theme_text_muted']};
    --theme-text-light: {$s['theme_text_light']};
    --theme-text-white: #ffffff;

    /* ===== BACKGROUND COLORS ===== */
    --theme-bg-body: {$s['theme_bg_body']};
    --theme-bg-light: {$s['theme_bg_light']};
    --theme-bg-gray: {$s['theme_bg_gray']};
    --theme-bg-dark: {$s['theme_bg_dark']};

    /* ===== BORDER COLORS ===== */
    --theme-border: {$s['theme_border']};
    --theme-border-light: {$s['theme_border_light']};
    --theme-border-dark: {$s['theme_border_dark']};

    /* ===== STATUS COLORS ===== */
    --theme-success: {$s['theme_success']};
    --theme-warning: {$s['theme_warning']};
    --theme-danger: {$s['theme_danger']};
    --theme-info: {$s['theme_info']};
    --theme-success-rgb: {$successRgb};
    --theme-warning-rgb: {$warningRgb};
    --theme-danger-rgb: {$dangerRgb};
    --theme-info-rgb: {$infoRgb};

    /* ===== STATUS LIGHT COLORS ===== */
    --theme-success-light: {$successLight};
    --theme-warning-light: {$warningLight};
    --theme-danger-light: {$dangerLight};
    --theme-info-light: {$infoLight};

    /* ===== STATUS HOVER COLORS ===== */
    --theme-success-hover: {$successHover};
    --theme-warning-hover: {$warningHover};
    --theme-danger-hover: {$dangerHover};
    --theme-info-hover: {$infoHover};

    /* ===== TEXT-ON COLORS (WCAG Contrast Safe) ===== */
    --theme-text-on-primary: {$textOnPrimary};
    --theme-text-on-secondary: {$textOnSecondary};
    --theme-text-on-success: {$textOnSuccess};
    --theme-text-on-warning: {$textOnWarning};
    --theme-text-on-danger: {$textOnDanger};
    --theme-text-on-info: {$textOnInfo};
    --theme-text-on-dark: {$textOnDark};
    --theme-text-on-light: {$textOnLight};

    /* ===== TYPOGRAPHY ===== */
    --theme-font-primary: '{$s['theme_font_primary']}', sans-serif;
    --theme-font-heading: '{$s['theme_font_heading']}', sans-serif;
    --theme-font-size-base: {$s['theme_font_size_base']};
    --theme-font-size-sm: {$s['theme_font_size_sm']};
    --theme-font-size-lg: {$s['theme_font_size_lg']};
    --theme-line-height: {$s['theme_line_height']};

    /* ===== BORDER RADIUS ===== */
    --theme-radius-xs: {$s['theme_radius_xs']};
    --theme-radius-sm: {$s['theme_radius_sm']};
    --theme-radius: {$s['theme_radius']};
    --theme-radius-lg: {$s['theme_radius_lg']};
    --theme-radius-xl: {$s['theme_radius_xl']};
    --theme-radius-pill: {$s['theme_radius_pill']};

    /* ===== SHADOWS ===== */
    --theme-shadow-xs: {$s['theme_shadow_xs']};
    --theme-shadow-sm: {$s['theme_shadow_sm']};
    --theme-shadow: {$s['theme_shadow']};
    --theme-shadow-lg: {$s['theme_shadow_lg']};
    --theme-shadow-xl: {$s['theme_shadow_xl']};

    /* ===== BUTTONS ===== */
    --theme-btn-padding-x: {$s['theme_btn_padding_x']};
    --theme-btn-padding-y: {$s['theme_btn_padding_y']};
    --theme-btn-font-size: {$s['theme_btn_font_size']};
    --theme-btn-font-weight: {$s['theme_btn_font_weight']};
    --theme-btn-radius: {$s['theme_btn_radius']};
    --theme-btn-shadow: {$s['theme_btn_shadow']};

    /* ===== CARDS ===== */
    --theme-card-bg: {$s['theme_card_bg']};
    --theme-card-border: {$s['theme_card_border']};
    --theme-card-radius: {$s['theme_card_radius']};
    --theme-card-shadow: {$s['theme_card_shadow']};
    --theme-card-hover-shadow: {$s['theme_card_hover_shadow']};
    --theme-card-padding: {$s['theme_card_padding']};

    /* ===== HEADER ===== */
    --theme-header-bg: {$s['theme_header_bg']};
    --theme-header-height: {$s['theme_header_height']};
    --theme-header-shadow: {$s['theme_header_shadow']};
    --theme-header-text: {$s['theme_header_text']};
    --theme-nav-link-color: {$s['theme_nav_link_color']};
    --theme-nav-link-hover: {$s['theme_nav_link_hover']};
    --theme-nav-font-size: {$s['theme_nav_font_size']};
    --theme-nav-font-weight: {$s['theme_nav_font_weight']};

    /* ===== FOOTER ===== */
    --theme-footer-bg: {$s['theme_footer_bg']};
    --theme-footer-text: {$s['theme_footer_text']};
    --theme-footer-text-muted: {$s['theme_footer_text_muted']};
    --theme-footer-link: {$s['theme_footer_link']};
    --theme-footer-link-hover: {$s['theme_footer_link_hover']};
    --theme-footer-border: {$s['theme_footer_border']};
    --theme-footer-padding: {$s['theme_footer_padding']};

    /* ===== INPUTS ===== */
    --theme-input-height: {$s['theme_input_height']};
    --theme-input-bg: {$s['theme_input_bg']};
    --theme-input-border: {$s['theme_input_border']};
    --theme-input-radius: {$s['theme_input_radius']};
    --theme-input-focus-border: {$s['theme_input_focus_border']};
    --theme-input-focus-shadow: {$s['theme_input_focus_shadow']};
    --theme-input-placeholder: {$s['theme_input_placeholder']};

    /* ===== ITEM CARDS ===== */
    --theme-catalogItem-name-size: {$s['theme_item_name_size']};
    --theme-catalogItem-name-weight: {$s['theme_item_name_weight']};
    --theme-catalogItem-price-size: {$s['theme_item_price_size']};
    --theme-catalogItem-price-weight: {$s['theme_item_price_weight']};
    --theme-catalogItem-card-radius: {$s['theme_item_card_radius']};
    --theme-catalogItem-img-radius: {$s['theme_item_img_radius']};
    --theme-catalogItem-hover-scale: {$s['theme_item_hover_scale']};

    /* ===== MODALS ===== */
    --theme-modal-bg: {$s['theme_modal_bg']};
    --theme-modal-radius: {$s['theme_modal_radius']};
    --theme-modal-shadow: {$s['theme_modal_shadow']};
    --theme-modal-backdrop: {$s['theme_modal_backdrop']};
    --theme-modal-header-bg: {$s['theme_modal_header_bg']};

    /* ===== TABLES ===== */
    --theme-table-header-bg: {$s['theme_table_header_bg']};
    --theme-table-header-text: {$s['theme_table_header_text']};
    --theme-table-border: {$s['theme_table_border']};
    --theme-table-hover-bg: {$s['theme_table_hover_bg']};
    --theme-table-stripe-bg: {$s['theme_table_stripe_bg']};

    /* ===== BADGES ===== */
    --theme-badge-radius: {$s['theme_badge_radius']};
    --theme-badge-padding: {$s['theme_badge_padding']};
    --theme-badge-font-size: {$s['theme_badge_font_size']};
    --theme-badge-font-weight: {$s['theme_badge_font_weight']};

    /* ===== SCROLLBAR ===== */
    --theme-scrollbar-width: {$s['theme_scrollbar_width']};
    --theme-scrollbar-track: {$s['theme_scrollbar_track']};
    --theme-scrollbar-thumb: {$s['theme_scrollbar_thumb']};
    --theme-scrollbar-thumb-hover: {$s['theme_scrollbar_thumb_hover']};

    /* ===== LINK COLORS ===== */
    --theme-link: {$s['theme_primary']};
    --theme-link-hover: {$s['theme_primary_hover']};

    /* ===== SEMANTIC MAPPING ===== */
    --text-primary: var(--theme-text-primary);
    --text-secondary: var(--theme-text-secondary);
    --text-muted: var(--theme-text-muted);
    --action-primary: var(--theme-primary);
    --action-primary-hover: var(--theme-primary-hover);
    --surface-page: var(--theme-bg-body);
    --surface-card: var(--theme-card-bg);
    --border-default: var(--theme-border);
    --border-light: var(--theme-border-light);
}

/* ===== SCROLLBAR STYLING ===== */
::-webkit-scrollbar { width: var(--theme-scrollbar-width); height: var(--theme-scrollbar-width); }
::-webkit-scrollbar-track { background: var(--theme-scrollbar-track); }
::-webkit-scrollbar-thumb { background: var(--theme-scrollbar-thumb); border-radius: var(--theme-radius); }
::-webkit-scrollbar-thumb:hover { background: var(--theme-scrollbar-thumb-hover); }

/* ===== BODY STYLING ===== */
body {
    font-family: var(--theme-font-primary);
    font-size: var(--theme-font-size-base);
    line-height: var(--theme-line-height);
    color: var(--theme-text-primary);
    background-color: var(--theme-bg-body);
}

h1, h2, h3, h4, h5, h6 {
    font-family: var(--theme-font-heading);
}
CSS;
    }

    // =========================================================================
    // COLOR UTILITIES
    // =========================================================================

    /**
     * Convert hex color to RGB string
     */
    public function hexToRgb(string $hex): string
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "{$r}, {$g}, {$b}";
    }

    /**
     * Calculate relative luminance (WCAG 2.1)
     */
    public function getLuminance(string $hex): float
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Get best text color for WCAG contrast
     */
    public function getContrastTextColor(string $bgHex, string $dark = '#1f0300', string $light = '#ffffff'): string
    {
        $bgLuminance = $this->getLuminance($bgHex);
        $whiteLuminance = 1;
        $whiteContrast = ($whiteLuminance + 0.05) / ($bgLuminance + 0.05);

        $darkLuminance = $this->getLuminance($dark);
        $darkContrast = ($bgLuminance + 0.05) / ($darkLuminance + 0.05);

        return $whiteContrast > $darkContrast ? $light : $dark;
    }

    /**
     * Lighten a color
     */
    public function lightenColor(string $hex, float $percent = 0.9): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = round($r + (255 - $r) * $percent);
        $g = round($g + (255 - $g) * $percent);
        $b = round($b + (255 - $b) * $percent);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /**
     * Darken a color
     */
    public function darkenColor(string $hex, float $percent = 0.15): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        $r = round($r * (1 - $percent));
        $g = round($g * (1 - $percent));
        $b = round($b * (1 - $percent));

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    // =========================================================================
    // PRESETS
    // =========================================================================

    /**
     * Load preset definitions
     */
    protected function loadPresets(): void
    {
        $this->presets = [
            'saudi' => [
                // تراث سعودي - أخضر رسمي + ذهبي + بني صحراوي
                'theme_primary' => '#006c35',
                'theme_primary_hover' => '#005529',
                'theme_primary_dark' => '#004420',
                'theme_primary_light' => '#e8f5ed',
                'theme_secondary' => '#1a1510',
                'theme_secondary_hover' => '#2d261e',
                'theme_secondary_light' => '#45382a',
                'theme_text_primary' => '#1a1510',
                'theme_text_secondary' => '#3d3429',
                'theme_text_muted' => '#7a6f5f',
                'theme_text_light' => '#b8a992',
                'theme_bg_body' => '#fdfcfa',
                'theme_bg_light' => '#faf8f5',
                'theme_bg_gray' => '#f5f2ec',
                'theme_bg_dark' => '#1a1510',
                'theme_success' => '#10b981',
                'theme_warning' => '#d4af37',
                'theme_danger' => '#c53030',
                'theme_info' => '#2c7a7b',
                'theme_border' => '#d4c4a8',
                'theme_border_light' => '#e8dcc8',
                'theme_border_dark' => '#c9a962',
                'theme_font_primary' => 'Cairo',
                'theme_font_heading' => 'Cairo',
                'theme_font_size_base' => '15px',
                'theme_font_size_sm' => '13px',
                'theme_font_size_lg' => '18px',
                'theme_radius_xs' => '2px',
                'theme_radius_sm' => '4px',
                'theme_radius' => '6px',
                'theme_radius_lg' => '8px',
                'theme_radius_xl' => '10px',
                'theme_radius_pill' => '50px',
                'theme_shadow_sm' => '0 1px 2px rgba(26,21,16,0.06)',
                'theme_shadow' => '0 2px 8px rgba(26,21,16,0.08)',
                'theme_shadow_lg' => '0 6px 20px rgba(26,21,16,0.12)',
                'theme_btn_padding_x' => '22px',
                'theme_btn_padding_y' => '11px',
                'theme_btn_font_size' => '14px',
                'theme_btn_font_weight' => '600',
                'theme_btn_radius' => '4px',
                'theme_btn_shadow' => '0 1px 3px rgba(0,108,53,0.2)',
                'theme_card_bg' => '#ffffff',
                'theme_card_border' => '#e8dcc8',
                'theme_card_radius' => '8px',
                'theme_card_shadow' => '0 2px 6px rgba(26,21,16,0.06)',
                'theme_card_hover_shadow' => '0 6px 18px rgba(0,108,53,0.1)',
                'theme_card_padding' => '22px',
                'theme_item_name_size' => '14px',
                'theme_item_name_weight' => '500',
                'theme_item_price_size' => '16px',
                'theme_item_hover_scale' => '1.01',
                'theme_input_height' => '46px',
                'theme_input_bg' => '#ffffff',
                'theme_input_border' => '#d4c4a8',
                'theme_input_radius' => '4px',
                'theme_input_focus_border' => '#006c35',
                'theme_input_focus_shadow' => '0 0 0 3px rgba(0,108,53,0.12)',
                'theme_input_placeholder' => '#9c8d7a',
                'theme_header_bg' => '#ffffff',
                'theme_header_height' => '70px',
                'theme_header_shadow' => '0 1px 4px rgba(26,21,16,0.08)',
                'theme_nav_link_color' => '#3d3429',
                'theme_nav_link_hover' => '#006c35',
                'theme_nav_font_size' => '15px',
                'theme_nav_font_weight' => '500',
                'theme_footer_bg' => '#1a1510',
                'theme_footer_text' => '#f5f2ec',
                'theme_footer_text_muted' => '#b8a992',
                'theme_footer_link_hover' => '#c9a962',
                'theme_footer_padding' => '55px',
                'theme_footer_link' => '#d4c4a8',
                'theme_footer_border' => '#45382a',
                'theme_badge_radius' => '3px',
                'theme_badge_padding' => '4px 10px',
                'theme_badge_font_size' => '12px',
                'theme_badge_font_weight' => '600',
                'theme_scrollbar_width' => '8px',
                'theme_scrollbar_track' => '#f5f2ec',
                'theme_scrollbar_thumb' => '#d4c4a8',
                'theme_scrollbar_thumb_hover' => '#c9a962',
                'theme_modal_bg' => '#ffffff',
                'theme_modal_radius' => '10px',
                'theme_modal_backdrop' => 'rgba(26,21,16,0.65)',
                'theme_table_header_bg' => '#faf8f5',
                'theme_table_border' => '#e8dcc8',
                'theme_table_hover_bg' => '#f5f2ec',
            ],
        ];
    }
}

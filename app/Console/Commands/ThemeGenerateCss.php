<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ThemeGenerateCss extends Command
{
    protected $signature = 'theme:generate-css';
    protected $description = 'Regenerate theme-colors.css from database settings';

    public function handle()
    {
        $this->info('Regenerating theme-colors.css...');

        $ps = platformSettings();
        $cssPath = public_path('assets/front/css/theme-colors.css');

        // ==================================
        // PRIMARY COLORS
        // ==================================
        $primary = $ps->get('theme_primary', '#c3002f');
        $primaryHover = $ps->get('theme_primary_hover', '#a00025');
        $primaryDark = $ps->get('theme_primary_dark', '#8a0020');
        $primaryLight = $ps->get('theme_primary_light', '#fef2f4');

        // ==================================
        // SECONDARY COLORS
        // ==================================
        $secondary = $ps->get('theme_secondary', '#1f0300');
        $secondaryHover = $ps->get('theme_secondary_hover', '#351c1a');
        $secondaryLight = $ps->get('theme_secondary_light', '#4c3533');

        // ==================================
        // TEXT COLORS
        // ==================================
        $textPrimary = $ps->get('theme_text_primary', '#1f0300');
        $textSecondary = $ps->get('theme_text_secondary', '#4c3533');
        $textMuted = $ps->get('theme_text_muted', '#796866');
        $textLight = $ps->get('theme_text_light', '#9a8e8c');
        $textLighter = '#b7aead';

        // ==================================
        // BACKGROUND COLORS
        // ==================================
        $bgBody = $ps->get('theme_bg_body', '#ffffff');
        $bgLight = $ps->get('theme_bg_light', '#f8f7f7');
        $bgLighter = '#f6f6f6';
        $bgGray = $ps->get('theme_bg_gray', '#e9e6e6');
        $bgDark = $ps->get('theme_bg_dark', '#030712');

        // ==================================
        // STATUS COLORS
        // ==================================
        $success = $ps->get('theme_success', '#27be69');
        $warning = $ps->get('theme_warning', '#fac03c');
        $danger = $ps->get('theme_danger', '#f2415a');
        $info = $ps->get('theme_info', '#0ea5e9');

        // ==================================
        // AUTO-CALCULATED TEXT-ON COLORS (WCAG Contrast)
        // ==================================
        $textOnPrimary = $this->getContrastTextColor($primary);
        $textOnSecondary = $this->getContrastTextColor($secondary);
        $textOnSuccess = $this->getContrastTextColor($success);
        $textOnWarning = $this->getContrastTextColor($warning);
        $textOnDanger = $this->getContrastTextColor($danger);
        $textOnInfo = $this->getContrastTextColor($info);
        $textOnDark = $this->getContrastTextColor($bgDark);
        $textOnLight = $this->getContrastTextColor($bgLight);

        // ==================================
        // BORDER COLORS
        // ==================================
        $border = $ps->get('theme_border', '#d9d4d4');
        $borderLight = $ps->get('theme_border_light', '#e9e6e6');
        $borderDark = $ps->get('theme_border_dark', '#c7c0bf');

        // ==================================
        // HEADER & FOOTER
        // ==================================
        $headerBg = $ps->get('theme_header_bg', '#ffffff');
        $footerBg = $ps->get('theme_footer_bg', '#030712');
        $footerText = $ps->get('theme_footer_text', '#ffffff');
        $footerLinkHover = $ps->get('theme_footer_link_hover') ?? $primary;

        // ==================================
        // TYPOGRAPHY
        // ==================================
        $fontPrimary = $ps->get('theme_font_primary', 'Poppins');
        $fontHeading = $ps->get('theme_font_heading', 'Saira');
        $fontSizeBase = $ps->get('theme_font_size_base', '16px');
        $fontSizeSm = $ps->get('theme_font_size_sm', '14px');
        $fontSizeLg = $ps->get('theme_font_size_lg', '18px');
        $lineHeight = $ps->get('theme_line_height', '1.5');

        // ==================================
        // BORDER RADIUS
        // ==================================
        $radiusXs = $ps->get('theme_radius_xs', '3px');
        $radiusSm = $ps->get('theme_radius_sm', '4px');
        $radius = $ps->get('theme_radius', '8px');
        $radiusLg = $ps->get('theme_radius_lg', '12px');
        $radiusXl = $ps->get('theme_radius_xl', '16px');
        $radiusPill = $ps->get('theme_radius_pill', '50px');

        // ==================================
        // SHADOWS
        // ==================================
        $shadowXs = $ps->get('theme_shadow_xs', '0 1px 2px rgba(0,0,0,0.04)');
        $shadowSm = $ps->get('theme_shadow_sm', '0 1px 3px rgba(0,0,0,0.06)');
        $shadow = $ps->get('theme_shadow', '0 2px 8px rgba(0,0,0,0.1)');
        $shadowLg = $ps->get('theme_shadow_lg', '0 4px 16px rgba(0,0,0,0.15)');
        $shadowXl = $ps->get('theme_shadow_xl', '0 8px 30px rgba(0,0,0,0.2)');

        // ==================================
        // BUTTONS
        // ==================================
        $btnPaddingX = $ps->get('theme_btn_padding_x', '24px');
        $btnPaddingY = $ps->get('theme_btn_padding_y', '12px');
        $btnFontSize = $ps->get('theme_btn_font_size', '14px');
        $btnFontWeight = $ps->get('theme_btn_font_weight', '600');
        $btnRadius = $ps->get('theme_btn_radius', '8px');
        $btnShadow = $ps->get('theme_btn_shadow', 'none');

        // ==================================
        // CARDS
        // ==================================
        $cardBg = $ps->get('theme_card_bg', '#ffffff');
        $cardBorder = $ps->get('theme_card_border', '#e9e6e6');
        $cardRadius = $ps->get('theme_card_radius', '12px');
        $cardShadow = $ps->get('theme_card_shadow', '0 2px 8px rgba(0,0,0,0.08)');
        $cardHoverShadow = $ps->get('theme_card_hover_shadow', '0 4px 16px rgba(0,0,0,0.12)');
        $cardPadding = $ps->get('theme_card_padding', '20px');

        // ==================================
        // HEADER
        // ==================================
        $headerHeight = $ps->get('theme_header_height', '80px');
        $headerShadow = $ps->get('theme_header_shadow', '0 2px 10px rgba(0,0,0,0.1)');
        $headerText = $ps->get('theme_header_text', '#1f0300');
        $navLinkColor = $ps->get('theme_nav_link_color', '#1f0300');
        $navLinkHover = $ps->get('theme_nav_link_hover', '#c3002f');
        $navFontSize = $ps->get('theme_nav_font_size', '15px');
        $navFontWeight = $ps->get('theme_nav_font_weight', '500');

        // ==================================
        // FOOTER
        // ==================================
        $footerPadding = $ps->get('theme_footer_padding', '60px');
        $footerTextMuted = $ps->get('theme_footer_text_muted', '#d9d4d4');
        $footerLink = $ps->get('theme_footer_link', '#ffffff');
        $footerBorder = $ps->get('theme_footer_border', '#374151');

        // ==================================
        // INPUTS
        // ==================================
        $inputHeight = $ps->get('theme_input_height', '48px');
        $inputBg = $ps->get('theme_input_bg', '#ffffff');
        $inputBorder = $ps->get('theme_input_border', '#d9d4d4');
        $inputRadius = $ps->get('theme_input_radius', '8px');
        $inputFocusBorder = $ps->get('theme_input_focus_border') ?? $primary;
        $inputFocusShadow = $ps->get('theme_input_focus_shadow') ?? "0 0 0 3px rgba({$this->hexToRgb($primary)},0.1)";
        $inputPlaceholder = $ps->get('theme_input_placeholder', '#9a8e8c');

        // ==================================
        // ITEM CARDS
        // ==================================
        $itemNameSize = $ps->get('theme_item_name_size', '14px');
        $itemNameWeight = $ps->get('theme_item_name_weight', '500');
        $itemPriceSize = $ps->get('theme_item_price_size', '16px');
        $itemHoverScale = $ps->get('theme_item_hover_scale', '1.02');

        // ==================================
        // MODALS
        // ==================================
        $modalBg = $ps->get('theme_modal_bg', '#ffffff');
        $modalRadius = $ps->get('theme_modal_radius', '16px');
        $modalBackdrop = $ps->get('theme_modal_backdrop', 'rgba(0,0,0,0.5)');

        // ==================================
        // TABLES
        // ==================================
        $tableHeaderBg = $ps->get('theme_table_header_bg', '#f8f7f7');
        $tableBorder = $ps->get('theme_table_border', '#e9e6e6');
        $tableHoverBg = $ps->get('theme_table_hover_bg', '#f8f7f7');

        // ==================================
        // BADGES
        // ==================================
        $badgeRadius = $ps->get('theme_badge_radius', '20px');
        $badgePadding = $ps->get('theme_badge_padding', '4px 12px');
        $badgeFontSize = $ps->get('theme_badge_font_size', '12px');
        $badgeFontWeight = $ps->get('theme_badge_font_weight', '600');

        // ==================================
        // SCROLLBAR
        // ==================================
        $scrollbarWidth = $ps->get('theme_scrollbar_width', '10px');
        $scrollbarTrack = $ps->get('theme_scrollbar_track', '#f1f1f1');
        $scrollbarThumb = $ps->get('theme_scrollbar_thumb', '#c1c1c1');
        $scrollbarThumbHover = $ps->get('theme_scrollbar_thumb_hover', '#a1a1a1');

        // Convert hex to RGB
        $primaryRgb = $this->hexToRgb($primary);
        $successRgb = $this->hexToRgb($success);
        $warningRgb = $this->hexToRgb($warning);
        $dangerRgb = $this->hexToRgb($danger);
        $infoRgb = $this->hexToRgb($info);

        // Calculate light colors for status
        $successLight = $ps->get('theme_success_light') ?? $this->lightenColor($success, 0.9);
        $warningLight = $ps->get('theme_warning_light') ?? $this->lightenColor($warning, 0.9);
        $dangerLight = $ps->get('theme_danger_light') ?? $this->lightenColor($danger, 0.9);
        $infoLight = $ps->get('theme_info_light') ?? $this->lightenColor($info, 0.9);

        // Calculate hover colors for status
        $successHover = $ps->get('theme_success_hover') ?? $this->darkenColor($success, 0.15);
        $warningHover = $ps->get('theme_warning_hover') ?? $this->darkenColor($warning, 0.15);
        $dangerHover = $ps->get('theme_danger_hover') ?? $this->darkenColor($danger, 0.15);
        $infoHover = $ps->get('theme_info_hover') ?? $this->darkenColor($info, 0.15);

        $timestamp = now()->format('Y-m-d H:i:s');

        $css = <<<CSS
/**
 * ========================================
 * THEME BUILDER - Generated CSS Variables
 * ========================================
 * Generated from Operator Panel Theme Builder
 * Do not edit manually - changes will be overwritten
 * Generated at: {$timestamp}
 */
:root {
    /* ===== PRIMARY BRAND COLORS ===== */
    --theme-primary: {$primary};
    --theme-primary-hover: {$primaryHover};
    --theme-primary-dark: {$primaryDark};
    --theme-primary-light: {$primaryLight};
    --theme-primary-rgb: {$primaryRgb};

    /* ===== SECONDARY COLORS ===== */
    --theme-secondary: {$secondary};
    --theme-secondary-hover: {$secondaryHover};
    --theme-secondary-light: {$secondaryLight};

    /* ===== TEXT COLORS ===== */
    --theme-text-primary: {$textPrimary};
    --theme-text-secondary: {$textSecondary};
    --theme-text-muted: {$textMuted};
    --theme-text-light: {$textLight};
    --theme-text-lighter: {$textLighter};
    --theme-text-white: #ffffff;

    /* ===== BACKGROUND COLORS ===== */
    --theme-bg-body: {$bgBody};
    --theme-bg-light: {$bgLight};
    --theme-bg-lighter: {$bgLighter};
    --theme-bg-gray: {$bgGray};
    --theme-bg-dark: {$bgDark};

    /* ===== BORDER COLORS ===== */
    --theme-border: {$border};
    --theme-border-light: {$borderLight};
    --theme-border-dark: {$borderDark};

    /* ===== STATUS COLORS ===== */
    --theme-success: {$success};
    --theme-warning: {$warning};
    --theme-danger: {$danger};
    --theme-info: {$info};
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
    /* Auto-calculated based on background luminance */
    --theme-text-on-primary: {$textOnPrimary};
    --theme-text-on-secondary: {$textOnSecondary};
    --theme-text-on-success: {$textOnSuccess};
    --theme-text-on-warning: {$textOnWarning};
    --theme-text-on-danger: {$textOnDanger};
    --theme-text-on-info: {$textOnInfo};
    --theme-text-on-dark: {$textOnDark};
    --theme-text-on-light: {$textOnLight};

    /* ===== TYPOGRAPHY ===== */
    --theme-font-primary: '{$fontPrimary}', sans-serif;
    --theme-font-heading: '{$fontHeading}', sans-serif;
    --theme-font-size-base: {$fontSizeBase};
    --theme-font-size-sm: {$fontSizeSm};
    --theme-font-size-lg: {$fontSizeLg};
    --theme-line-height: {$lineHeight};

    /* ===== BORDER RADIUS ===== */
    --theme-radius-xs: {$radiusXs};
    --theme-radius-sm: {$radiusSm};
    --theme-radius: {$radius};
    --theme-radius-lg: {$radiusLg};
    --theme-radius-xl: {$radiusXl};
    --theme-radius-pill: {$radiusPill};

    /* ===== SHADOWS ===== */
    --theme-shadow-xs: {$shadowXs};
    --theme-shadow-sm: {$shadowSm};
    --theme-shadow: {$shadow};
    --theme-shadow-lg: {$shadowLg};
    --theme-shadow-xl: {$shadowXl};

    /* ===== BUTTONS ===== */
    --theme-btn-padding-x: {$btnPaddingX};
    --theme-btn-padding-y: {$btnPaddingY};
    --theme-btn-font-size: {$btnFontSize};
    --theme-btn-font-weight: {$btnFontWeight};
    --theme-btn-radius: {$btnRadius};
    --theme-btn-shadow: {$btnShadow};

    /* ===== CARDS ===== */
    --theme-card-bg: {$cardBg};
    --theme-card-border: {$cardBorder};
    --theme-card-radius: {$cardRadius};
    --theme-card-shadow: {$cardShadow};
    --theme-card-hover-shadow: {$cardHoverShadow};
    --theme-card-padding: {$cardPadding};

    /* ===== HEADER ===== */
    --theme-header-bg: {$headerBg};
    --theme-header-height: {$headerHeight};
    --theme-header-shadow: {$headerShadow};
    --theme-header-text: {$headerText};
    --theme-nav-link-color: {$navLinkColor};
    --theme-nav-link-hover: {$navLinkHover};
    --theme-nav-font-size: {$navFontSize};
    --theme-nav-font-weight: {$navFontWeight};

    /* ===== FOOTER ===== */
    --theme-footer-bg: {$footerBg};
    --theme-footer-text: {$footerText};
    --theme-footer-text-muted: {$footerTextMuted};
    --theme-footer-link: {$footerLink};
    --theme-footer-link-hover: {$footerLinkHover};
    --theme-footer-border: {$footerBorder};
    --theme-footer-padding: {$footerPadding};

    /* ===== INPUTS ===== */
    --theme-input-height: {$inputHeight};
    --theme-input-bg: {$inputBg};
    --theme-input-border: {$inputBorder};
    --theme-input-radius: {$inputRadius};
    --theme-input-focus-border: {$inputFocusBorder};
    --theme-input-focus-shadow: {$inputFocusShadow};
    --theme-input-placeholder: {$inputPlaceholder};

    /* ===== ITEM CARDS ===== */
    --theme-catalogItem-name-size: {$itemNameSize};
    --theme-catalogItem-name-weight: {$itemNameWeight};
    --theme-catalogItem-price-size: {$itemPriceSize};
    --theme-catalogItem-hover-scale: {$itemHoverScale};

    /* ===== MODALS ===== */
    --theme-modal-bg: {$modalBg};
    --theme-modal-radius: {$modalRadius};
    --theme-modal-backdrop: {$modalBackdrop};

    /* ===== TABLES ===== */
    --theme-table-header-bg: {$tableHeaderBg};
    --theme-table-border: {$tableBorder};
    --theme-table-hover-bg: {$tableHoverBg};

    /* ===== BADGES ===== */
    --theme-badge-radius: {$badgeRadius};
    --theme-badge-padding: {$badgePadding};
    --theme-badge-font-size: {$badgeFontSize};
    --theme-badge-font-weight: {$badgeFontWeight};

    /* ===== SCROLLBAR ===== */
    --theme-scrollbar-width: {$scrollbarWidth};
    --theme-scrollbar-track: {$scrollbarTrack};
    --theme-scrollbar-thumb: {$scrollbarThumb};
    --theme-scrollbar-thumb-hover: {$scrollbarThumbHover};

    /* ===== LINK COLORS ===== */
    --theme-link: {$primary};
    --theme-link-hover: {$primaryHover};
}
CSS;

        file_put_contents($cssPath, $css);

        $this->info("Generated theme-colors.css successfully!");
        $this->line("  Path: {$cssPath}");
        $this->line("  Primary: {$primary}");
        $this->line("  Text-on-primary: {$textOnPrimary}");

        return Command::SUCCESS;
    }

    /**
     * Calculate relative luminance (WCAG 2.1)
     */
    private function getLuminance($hex)
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
    private function getContrastTextColor($bgHex, $darkText = '#1f0300', $lightText = '#ffffff')
    {
        $bgLuminance = $this->getLuminance($bgHex);
        $whiteLuminance = 1;
        $whiteContrast = ($whiteLuminance + 0.05) / ($bgLuminance + 0.05);

        $darkLuminance = $this->getLuminance($darkText);
        $darkContrast = ($bgLuminance + 0.05) / ($darkLuminance + 0.05);

        return $whiteContrast > $darkContrast ? $lightText : $darkText;
    }

    /**
     * Convert hex to RGB string
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        return hexdec(substr($hex, 0, 2)) . ', ' . hexdec(substr($hex, 2, 2)) . ', ' . hexdec(substr($hex, 4, 2));
    }

    /**
     * Lighten a color
     */
    private function lightenColor($hex, $percent = 0.9)
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
    private function darkenColor($hex, $percent = 0.15)
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
}

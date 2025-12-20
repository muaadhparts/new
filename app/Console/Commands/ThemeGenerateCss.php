<?php

namespace App\Console\Commands;

use App\Models\Generalsetting;
use Illuminate\Console\Command;

class ThemeGenerateCss extends Command
{
    protected $signature = 'theme:generate-css';
    protected $description = 'Regenerate theme-colors.css from database settings';

    public function handle()
    {
        $this->info('Regenerating theme-colors.css...');

        $gs = Generalsetting::findOrFail(1);
        $cssPath = public_path('assets/front/css/theme-colors.css');

        // ==================================
        // PRIMARY COLORS
        // ==================================
        $primary = $gs->theme_primary ?? '#c3002f';
        $primaryHover = $gs->theme_primary_hover ?? '#a00025';
        $primaryDark = $gs->theme_primary_dark ?? '#8a0020';
        $primaryLight = $gs->theme_primary_light ?? '#fef2f4';

        // ==================================
        // SECONDARY COLORS
        // ==================================
        $secondary = $gs->theme_secondary ?? '#1f0300';
        $secondaryHover = $gs->theme_secondary_hover ?? '#351c1a';
        $secondaryLight = $gs->theme_secondary_light ?? '#4c3533';

        // ==================================
        // TEXT COLORS
        // ==================================
        $textPrimary = $gs->theme_text_primary ?? '#1f0300';
        $textSecondary = $gs->theme_text_secondary ?? '#4c3533';
        $textMuted = $gs->theme_text_muted ?? '#796866';
        $textLight = $gs->theme_text_light ?? '#9a8e8c';
        $textLighter = '#b7aead';

        // ==================================
        // BACKGROUND COLORS
        // ==================================
        $bgBody = $gs->theme_bg_body ?? '#ffffff';
        $bgLight = $gs->theme_bg_light ?? '#f8f7f7';
        $bgLighter = '#f6f6f6';
        $bgGray = $gs->theme_bg_gray ?? '#e9e6e6';
        $bgDark = $gs->theme_bg_dark ?? '#030712';

        // ==================================
        // STATUS COLORS
        // ==================================
        $success = $gs->theme_success ?? '#27be69';
        $warning = $gs->theme_warning ?? '#fac03c';
        $danger = $gs->theme_danger ?? '#f2415a';
        $info = $gs->theme_info ?? '#0ea5e9';

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
        $border = $gs->theme_border ?? '#d9d4d4';
        $borderLight = $gs->theme_border_light ?? '#e9e6e6';
        $borderDark = $gs->theme_border_dark ?? '#c7c0bf';

        // ==================================
        // HEADER & FOOTER
        // ==================================
        $headerBg = $gs->theme_header_bg ?? '#ffffff';
        $footerBg = $gs->theme_footer_bg ?? '#030712';
        $footerText = $gs->theme_footer_text ?? '#ffffff';
        $footerLinkHover = $gs->theme_footer_link_hover ?? $primary;

        // ==================================
        // TYPOGRAPHY
        // ==================================
        $fontPrimary = $gs->theme_font_primary ?? 'Poppins';
        $fontHeading = $gs->theme_font_heading ?? 'Saira';
        $fontSizeBase = $gs->theme_font_size_base ?? '16px';
        $fontSizeSm = $gs->theme_font_size_sm ?? '14px';
        $fontSizeLg = $gs->theme_font_size_lg ?? '18px';
        $lineHeight = $gs->theme_line_height ?? '1.5';

        // ==================================
        // BORDER RADIUS
        // ==================================
        $radiusXs = $gs->theme_radius_xs ?? '3px';
        $radiusSm = $gs->theme_radius_sm ?? '4px';
        $radius = $gs->theme_radius ?? '8px';
        $radiusLg = $gs->theme_radius_lg ?? '12px';
        $radiusXl = $gs->theme_radius_xl ?? '16px';
        $radiusPill = $gs->theme_radius_pill ?? '50px';

        // ==================================
        // SHADOWS
        // ==================================
        $shadowXs = $gs->theme_shadow_xs ?? '0 1px 2px rgba(0,0,0,0.04)';
        $shadowSm = $gs->theme_shadow_sm ?? '0 1px 3px rgba(0,0,0,0.06)';
        $shadow = $gs->theme_shadow ?? '0 2px 8px rgba(0,0,0,0.1)';
        $shadowLg = $gs->theme_shadow_lg ?? '0 4px 16px rgba(0,0,0,0.15)';
        $shadowXl = $gs->theme_shadow_xl ?? '0 8px 30px rgba(0,0,0,0.2)';

        // ==================================
        // BUTTONS
        // ==================================
        $btnPaddingX = $gs->theme_btn_padding_x ?? '24px';
        $btnPaddingY = $gs->theme_btn_padding_y ?? '12px';
        $btnFontSize = $gs->theme_btn_font_size ?? '14px';
        $btnFontWeight = $gs->theme_btn_font_weight ?? '600';
        $btnRadius = $gs->theme_btn_radius ?? '8px';
        $btnShadow = $gs->theme_btn_shadow ?? 'none';

        // ==================================
        // CARDS
        // ==================================
        $cardBg = $gs->theme_card_bg ?? '#ffffff';
        $cardBorder = $gs->theme_card_border ?? '#e9e6e6';
        $cardRadius = $gs->theme_card_radius ?? '12px';
        $cardShadow = $gs->theme_card_shadow ?? '0 2px 8px rgba(0,0,0,0.08)';
        $cardHoverShadow = $gs->theme_card_hover_shadow ?? '0 4px 16px rgba(0,0,0,0.12)';
        $cardPadding = $gs->theme_card_padding ?? '20px';

        // ==================================
        // HEADER
        // ==================================
        $headerHeight = $gs->theme_header_height ?? '80px';
        $headerShadow = $gs->theme_header_shadow ?? '0 2px 10px rgba(0,0,0,0.1)';
        $headerText = $gs->theme_header_text ?? '#1f0300';
        $navLinkColor = $gs->theme_nav_link_color ?? '#1f0300';
        $navLinkHover = $gs->theme_nav_link_hover ?? '#c3002f';
        $navFontSize = $gs->theme_nav_font_size ?? '15px';
        $navFontWeight = $gs->theme_nav_font_weight ?? '500';

        // ==================================
        // FOOTER
        // ==================================
        $footerPadding = $gs->theme_footer_padding ?? '60px';
        $footerTextMuted = $gs->theme_footer_text_muted ?? '#d9d4d4';
        $footerLink = $gs->theme_footer_link ?? '#ffffff';
        $footerBorder = $gs->theme_footer_border ?? '#374151';

        // ==================================
        // INPUTS
        // ==================================
        $inputHeight = $gs->theme_input_height ?? '48px';
        $inputBg = $gs->theme_input_bg ?? '#ffffff';
        $inputBorder = $gs->theme_input_border ?? '#d9d4d4';
        $inputRadius = $gs->theme_input_radius ?? '8px';
        $inputFocusBorder = $gs->theme_input_focus_border ?? $primary;
        $inputFocusShadow = $gs->theme_input_focus_shadow ?? "0 0 0 3px rgba({$this->hexToRgb($primary)},0.1)";
        $inputPlaceholder = $gs->theme_input_placeholder ?? '#9a8e8c';

        // ==================================
        // PRODUCT CARDS
        // ==================================
        $productTitleSize = $gs->theme_product_title_size ?? '14px';
        $productTitleWeight = $gs->theme_product_title_weight ?? '500';
        $productPriceSize = $gs->theme_product_price_size ?? '16px';
        $productHoverScale = $gs->theme_product_hover_scale ?? '1.02';

        // ==================================
        // MODALS
        // ==================================
        $modalBg = $gs->theme_modal_bg ?? '#ffffff';
        $modalRadius = $gs->theme_modal_radius ?? '16px';
        $modalBackdrop = $gs->theme_modal_backdrop ?? 'rgba(0,0,0,0.5)';

        // ==================================
        // TABLES
        // ==================================
        $tableHeaderBg = $gs->theme_table_header_bg ?? '#f8f7f7';
        $tableBorder = $gs->theme_table_border ?? '#e9e6e6';
        $tableHoverBg = $gs->theme_table_hover_bg ?? '#f8f7f7';

        // ==================================
        // BADGES
        // ==================================
        $badgeRadius = $gs->theme_badge_radius ?? '20px';
        $badgePadding = $gs->theme_badge_padding ?? '4px 12px';
        $badgeFontSize = $gs->theme_badge_font_size ?? '12px';
        $badgeFontWeight = $gs->theme_badge_font_weight ?? '600';

        // ==================================
        // SCROLLBAR
        // ==================================
        $scrollbarWidth = $gs->theme_scrollbar_width ?? '10px';
        $scrollbarTrack = $gs->theme_scrollbar_track ?? '#f1f1f1';
        $scrollbarThumb = $gs->theme_scrollbar_thumb ?? '#c1c1c1';
        $scrollbarThumbHover = $gs->theme_scrollbar_thumb_hover ?? '#a1a1a1';

        // Convert hex to RGB
        $primaryRgb = $this->hexToRgb($primary);
        $successRgb = $this->hexToRgb($success);
        $warningRgb = $this->hexToRgb($warning);
        $dangerRgb = $this->hexToRgb($danger);
        $infoRgb = $this->hexToRgb($info);

        // Calculate light colors for status
        $successLight = $gs->theme_success_light ?? $this->lightenColor($success, 0.9);
        $warningLight = $gs->theme_warning_light ?? $this->lightenColor($warning, 0.9);
        $dangerLight = $gs->theme_danger_light ?? $this->lightenColor($danger, 0.9);
        $infoLight = $gs->theme_info_light ?? $this->lightenColor($info, 0.9);

        // Calculate hover colors for status
        $successHover = $gs->theme_success_hover ?? $this->darkenColor($success, 0.15);
        $warningHover = $gs->theme_warning_hover ?? $this->darkenColor($warning, 0.15);
        $dangerHover = $gs->theme_danger_hover ?? $this->darkenColor($danger, 0.15);
        $infoHover = $gs->theme_info_hover ?? $this->darkenColor($info, 0.15);

        $timestamp = now()->format('Y-m-d H:i:s');

        $css = <<<CSS
/**
 * ========================================
 * THEME BUILDER - Generated CSS Variables
 * ========================================
 * Generated from Admin Panel Theme Builder
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

    /* ===== PRODUCT CARDS ===== */
    --theme-product-title-size: {$productTitleSize};
    --theme-product-title-weight: {$productTitleWeight};
    --theme-product-price-size: {$productPriceSize};
    --theme-product-hover-scale: {$productHoverScale};

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

<?php

namespace App\Http\Controllers\Operator;

use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use Validator;

class MuaadhSettingController extends OperatorBaseController
{
    protected $rules =
        [
        'logo' => 'mimes:jpeg,jpg,png,svg',
        'favicon' => 'mimes:jpeg,jpg,png,svg',
        'loader' => 'mimes:gif',
        'admin_loader' => 'mimes:gif',
        'affilate_banner' => 'mimes:jpeg,jpg,png,svg',
        'error_banner_404' => 'mimes:jpeg,jpg,png,svg',
        'error_banner_500' => 'mimes:jpeg,jpg,png,svg',
        'popup_background' => 'mimes:jpeg,jpg,png,svg',
        'invoice_logo' => 'mimes:jpeg,jpg,png,svg',
        'user_image' => 'mimes:jpeg,jpg,png,svg',
        'footer_logo' => 'mimes:jpeg,jpg,png,svg',
    ];

    public function updateTheme(Request $request)
    {
        PlatformSetting::set('theme', 'theme', $request->theme);
        cache()->forget('platform_settings_context');
        return back()->with('success','Home Updated Successfully');
    }

    private function setEnv($key, $value, $prev)
    {
        file_put_contents(app()->environmentFilePath(), str_replace(
            $key . '=' . $prev,
            $key . '=' . $value,
            file_get_contents(app()->environmentFilePath())
        ));
    }

    public function paymentsinfo()
    {
        return view('operator.muaadhsetting.paymentsinfo');
    }

    public function logo()
    {
        return view('operator.muaadhsetting.logo');
    }

    public function favicon()
    {
        return view('operator.muaadhsetting.favicon');
    }

    public function loader()
    {
        return view('operator.muaadhsetting.loader');
    }

    public function websitecontent()
    {
        return view('operator.muaadhsetting.websitecontent');
    }
    public function popup()
    {
        return view('operator.muaadhsetting.popup');
    }

    // breadcrumb() method removed - using modern minimal design

    public function footer()
    {
        return view('operator.muaadhsetting.footer');
    }

    public function affilate()
    {
        return view('operator.muaadhsetting.affilate');
    }

    public function error_banner()
    {
        return view('operator.muaadhsetting.error_banner');
    }

    public function maintain()
    {
        return view('operator.muaadhsetting.maintain');
    }

    public function user_image()
    {
        return view('operator.muaadhsetting.user_image');
    }

    // Genereal Settings All post requests will be done in this method
    public function generalupdate(Request $request)
    {
        //--- Validation Section
        $validator = Validator::make($request->all(), $this->rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $input = $request->except(['_token', '_method']);
        $ps = platformSettings();

        // Handle file uploads
        $fileFields = ['logo', 'favicon', 'loader', 'admin_loader', 'affilate_banner',
                       'error_banner_404', 'error_banner_500', 'popup_background',
                       'invoice_logo', 'user_image', 'footer_logo'];

        foreach ($fileFields as $field) {
            if ($file = $request->file($field)) {
                $name = \PriceHelper::ImageCreateName($file);
                // Delete old file if exists
                $oldFile = $ps->get($field);
                if ($oldFile && file_exists(public_path('assets/images/' . $oldFile))) {
                    @unlink(public_path('assets/images/' . $oldFile));
                }
                // Upload new file
                $file->move(public_path('assets/images'), $name);
                $input[$field] = $name;
            }
        }

        if ($request->capcha_secret_key) {
            $this->setEnv('NOCAPTCHA_SECRET', $request->capcha_secret_key, env('NOCAPTCHA_SECRET'));
        }
        if ($request->capcha_site_key) {
            $this->setEnv('NOCAPTCHA_SITEKEY', $request->capcha_site_key, env('NOCAPTCHA_SITEKEY'));
        }

        // Save all settings to platform_settings
        foreach ($input as $key => $value) {
            if (!is_null($value) && !in_array($key, ['capcha_secret_key', 'capcha_site_key'])) {
                PlatformSetting::set('general', $key, $value);
            }
        }

        cache()->forget('platform_settings_context');
        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function generalupdatepayment(Request $request)
    {
        //--- Validation Section
        $validator = Validator::make($request->all(), $this->rules);

        if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $input = $request->except(['_token', '_method']);

        if ($request->instamojo_sandbox == "") {
            $input['instamojo_sandbox'] = 0;
        }

        if ($request->paypal_mode == "") {
            $input['paypal_mode'] = 'live';
        } else {
            $input['paypal_mode'] = 'sandbox';
        }

        if ($request->paytm_mode == "") {
            $input['paytm_mode'] = 'live';
        } else {
            $input['paytm_mode'] = 'sandbox';
        }

        // Save all payment settings to platform_settings
        foreach ($input as $key => $value) {
            if (!is_null($value)) {
                PlatformSetting::set('payment', $key, $value);
            }
        }

        cache()->forget('platform_settings_context');

        //--- Logic Section Ends

        //--- Redirect Section
        $msg = __('Data Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function generalMailUpdate(Request $request)
    {
        $input = $request->except(['_token', '_method']);

        // Save all mail settings to platform_settings
        foreach ($input as $key => $value) {
            if (!is_null($value)) {
                PlatformSetting::set('mail', $key, $value);
            }
        }

        cache()->forget('platform_settings_context');
        //--- Redirect Section
        $msg = 'Mail Data Updated Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    // Status Change Method -> GET Request
    public function status($field, $value)
    {
        $ps = platformSettings();
        $prev = '';
        if ($field == 'is_debug') {
            $prev = $ps->get('is_debug') == 1 ? 'true' : 'false';
        }

        PlatformSetting::set('general', $field, $value);

        if ($field == 'is_debug') {
            $now = $value == 1 ? 'true' : 'false';
            $this->setEnv('APP_DEBUG', $now, $prev);
        }
        cache()->forget('platform_settings_context');
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function homepage()
    {
        return view('operator.muaadhsetting.homepage');
    }


    public function otpConfig() {
        return view("admin.muaadhsetting.otp_config");
    }

    /**
     * Show theme colors settings page
     */
    public function themeColors()
    {
        return view('operator.muaadhsetting.theme_colors');
    }

    /**
     * Update theme colors - Complete Theme Builder System
     */
    public function updateThemeColors(Request $request)
    {
        // Define all theme settings with their defaults
        $themeDefaults = [
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

            // HEADER & FOOTER COLORS
            'theme_header_bg' => '#ffffff',
            'theme_footer_bg' => '#030712',
            'theme_footer_text' => '#ffffff',
            'theme_footer_link_hover' => '#c3002f',

            // TYPOGRAPHY
            'theme_font_primary' => 'Poppins',
            'theme_font_heading' => 'Saira',
            'theme_font_size_base' => '16px',
            'theme_font_size_sm' => '14px',
            'theme_font_size_lg' => '18px',
            'theme_line_height' => '1.5',

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

            // HEADER
            'theme_header_height' => '80px',
            'theme_header_shadow' => '0 2px 10px rgba(0,0,0,0.1)',
            'theme_header_text' => '#1f0300',
            'theme_nav_link_color' => '#1f0300',
            'theme_nav_link_hover' => '#c3002f',
            'theme_nav_font_size' => '15px',
            'theme_nav_font_weight' => '500',

            // FOOTER
            'theme_footer_padding' => '60px',
            'theme_footer_text_muted' => '#d9d4d4',
            'theme_footer_link' => '#ffffff',
            'theme_footer_border' => '#374151',

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

            // FORMS
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

            // SEARCH COMPONENT
            'theme_search_bg' => '#ffffff',
            'theme_search_border' => '#e9e6e6',
            'theme_search_radius' => '50px',
            'theme_search_height' => '50px',
            'theme_search_shadow' => '0 4px 15px rgba(0,0,0,0.08)',

            // CATEGORY CARDS
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

            // SOCIAL COLORS
            'theme_facebook' => '#1877f2',
            'theme_twitter' => '#1da1f2',
            'theme_instagram' => '#e4405f',
            'theme_whatsapp' => '#25d366',
            'theme_youtube' => '#ff0000',
            'theme_linkedin' => '#0a66c2',
        ];

        // Save all theme settings to platform_settings
        foreach ($themeDefaults as $key => $default) {
            $value = $request->$key ?? $default;
            PlatformSetting::set('theme', $key, $value);
        }

        // Clear cache
        cache()->forget('platform_settings_context');

        // Regenerate CSS file with all theme variables
        $this->generateThemeCss(platformSettings());

        return back()->with('success', __('Theme Builder Settings Updated Successfully'));
    }

    /**
     * Calculate relative luminance of a hex color (WCAG 2.1)
     * Returns value between 0 (black) and 1 (white)
     */
    private function getLuminance($hex)
    {
        // Remove # if present
        $hex = ltrim($hex, '#');

        // Parse RGB values
        $r = hexdec(substr($hex, 0, 2)) / 255;
        $g = hexdec(substr($hex, 2, 2)) / 255;
        $b = hexdec(substr($hex, 4, 2)) / 255;

        // Apply gamma correction
        $r = $r <= 0.03928 ? $r / 12.92 : pow(($r + 0.055) / 1.055, 2.4);
        $g = $g <= 0.03928 ? $g / 12.92 : pow(($g + 0.055) / 1.055, 2.4);
        $b = $b <= 0.03928 ? $b / 12.92 : pow(($b + 0.055) / 1.055, 2.4);

        // Calculate luminance
        return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * Get best text color (black or white) for contrast on given background
     * Uses WCAG contrast ratio calculation
     */
    private function getContrastTextColor($bgHex, $darkText = '#1f0300', $lightText = '#ffffff')
    {
        $bgLuminance = $this->getLuminance($bgHex);

        // Calculate contrast ratio with white text
        $whiteLuminance = 1; // White = 1
        $whiteContrast = ($whiteLuminance + 0.05) / ($bgLuminance + 0.05);

        // Calculate contrast ratio with dark text
        $darkLuminance = $this->getLuminance($darkText);
        $darkContrast = ($bgLuminance + 0.05) / ($darkLuminance + 0.05);

        // Return color with better contrast
        return $whiteContrast > $darkContrast ? $lightText : $darkText;
    }

    /**
     * Generate theme CSS file with all database colors
     * Complete Theme Builder System
     */
    private function generateThemeCss($ps)
    {
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
        $footerLinkHover = $ps->get('theme_footer_link_hover', $primary);

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
        // SPACING
        // ==================================
        $spacingXs = $ps->get('theme_spacing_xs', '4px');
        $spacingSm = $ps->get('theme_spacing_sm', '8px');
        $spacing = $ps->get('theme_spacing', '16px');
        $spacingLg = $ps->get('theme_spacing_lg', '24px');
        $spacingXl = $ps->get('theme_spacing_xl', '32px');

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
        // FOOTER (Extended)
        // ==================================
        $footerPadding = $ps->get('theme_footer_padding', '60px');
        $footerTextMuted = $ps->get('theme_footer_text_muted', '#d9d4d4');
        $footerLink = $ps->get('theme_footer_link', '#ffffff');
        $footerBorder = $ps->get('theme_footer_border', '#374151');

        // ==================================
        // ITEM CARDS
        // ==================================
        $itemNameSize = $ps->get('theme_item_name_size', '14px');
        $itemNameWeight = $ps->get('theme_item_name_weight', '500');
        $itemPriceSize = $ps->get('theme_item_price_size', '16px');
        $itemPriceWeight = $ps->get('theme_item_price_weight', '700');
        $itemCardRadius = $ps->get('theme_item_card_radius', '12px');
        $itemImgRadius = $ps->get('theme_item_img_radius', '8px');
        $itemHoverScale = $ps->get('theme_item_hover_scale', '1.02');

        // ==================================
        // MODALS
        // ==================================
        $modalBg = $ps->get('theme_modal_bg', '#ffffff');
        $modalRadius = $ps->get('theme_modal_radius', '16px');
        $modalShadow = $ps->get('theme_modal_shadow', '0 25px 50px rgba(0,0,0,0.25)');
        $modalBackdrop = $ps->get('theme_modal_backdrop', 'rgba(0,0,0,0.5)');
        $modalHeaderBg = $ps->get('theme_modal_header_bg', '#f8f7f7');

        // ==================================
        // TABLES
        // ==================================
        $tableHeaderBg = $ps->get('theme_table_header_bg', '#f8f7f7');
        $tableHeaderText = $ps->get('theme_table_header_text', '#1f0300');
        $tableBorder = $ps->get('theme_table_border', '#e9e6e6');
        $tableHoverBg = $ps->get('theme_table_hover_bg', '#f8f7f7');
        $tableStripeBg = $ps->get('theme_table_stripe_bg', '#fafafa');

        // ==================================
        // FORMS
        // ==================================
        $inputHeight = $ps->get('theme_input_height', '48px');
        $inputBg = $ps->get('theme_input_bg', '#ffffff');
        $inputBorder = $ps->get('theme_input_border', '#d9d4d4');
        $inputRadius = $ps->get('theme_input_radius', '8px');
        $inputFocusBorder = $ps->get('theme_input_focus_border', '#c3002f');
        $inputFocusShadow = $ps->get('theme_input_focus_shadow', '0 0 0 3px rgba(195,0,47,0.1)');
        $inputPlaceholder = $ps->get('theme_input_placeholder', '#9a8e8c');

        // ==================================
        // BADGES
        // ==================================
        $badgeRadius = $ps->get('theme_badge_radius', '20px');
        $badgePadding = $ps->get('theme_badge_padding', '4px 12px');
        $badgeFontSize = $ps->get('theme_badge_font_size', '12px');
        $badgeFontWeight = $ps->get('theme_badge_font_weight', '600');

        // ==================================
        // CHIPS
        // ==================================
        $chipBg = $ps->get('theme_chip_bg', '#f8f7f7');
        $chipText = $ps->get('theme_chip_text', '#4c3533');
        $chipRadius = $ps->get('theme_chip_radius', '6px');
        $chipBorder = $ps->get('theme_chip_border', '#e9e6e6');

        // ==================================
        // SCROLLBAR
        // ==================================
        $scrollbarWidth = $ps->get('theme_scrollbar_width', '10px');
        $scrollbarTrack = $ps->get('theme_scrollbar_track', '#f1f1f1');
        $scrollbarThumb = $ps->get('theme_scrollbar_thumb', '#c1c1c1');
        $scrollbarThumbHover = $ps->get('theme_scrollbar_thumb_hover', '#a1a1a1');

        // ==================================
        // TRANSITIONS
        // ==================================
        $transitionFast = $ps->get('theme_transition_fast', 'all 0.15s ease');
        $transition = $ps->get('theme_transition', 'all 0.3s ease');
        $transitionSlow = $ps->get('theme_transition_slow', 'all 0.5s ease');

        // ==================================
        // SEARCH COMPONENT
        // ==================================
        $searchBg = $ps->get('theme_search_bg', '#ffffff');
        $searchBorder = $ps->get('theme_search_border', '#e9e6e6');
        $searchRadius = $ps->get('theme_search_radius', '50px');
        $searchHeight = $ps->get('theme_search_height', '50px');
        $searchShadow = $ps->get('theme_search_shadow', '0 4px 15px rgba(0,0,0,0.08)');

        // ==================================
        // CATEGORY CARDS
        // ==================================
        $categoryBg = $ps->get('theme_category_bg', '#ffffff');
        $categoryRadius = $ps->get('theme_category_radius', '12px');
        $categoryShadow = $ps->get('theme_category_shadow', '0 2px 8px rgba(0,0,0,0.08)');
        $categoryHoverShadow = $ps->get('theme_category_hover_shadow', '0 8px 25px rgba(0,0,0,0.15)');

        // ==================================
        // PAGINATION
        // ==================================
        $paginationSize = $ps->get('theme_pagination_size', '40px');
        $paginationRadius = $ps->get('theme_pagination_radius', '8px');
        $paginationGap = $ps->get('theme_pagination_gap', '5px');

        // ==================================
        // ALERTS
        // ==================================
        $alertRadius = $ps->get('theme_alert_radius', '8px');
        $alertPadding = $ps->get('theme_alert_padding', '16px 20px');

        // ==================================
        // BREADCRUMB
        // ==================================
        $breadcrumbBg = $ps->get('theme_breadcrumb_bg', '#f8f7f7');
        $breadcrumbSeparator = $ps->get('theme_breadcrumb_separator', '/');
        $breadcrumbText = $ps->get('theme_breadcrumb_text', '#796866');

        // ==================================
        // SOCIAL COLORS
        // ==================================
        $facebook = $ps->get('theme_facebook', '#1877f2');
        $twitter = $ps->get('theme_twitter', '#1da1f2');
        $instagram = $ps->get('theme_instagram', '#e4405f');
        $whatsapp = $ps->get('theme_whatsapp', '#25d366');
        $youtube = $ps->get('theme_youtube', '#ff0000');
        $linkedin = $ps->get('theme_linkedin', '#0a66c2');

        // Convert hex to RGB for opacity usage
        $primaryRgb = $this->hexToRgb($primary);
        $successRgb = $this->hexToRgb($success);
        $warningRgb = $this->hexToRgb($warning);
        $dangerRgb = $this->hexToRgb($danger);
        $infoRgb = $this->hexToRgb($info);

        $css = <<<CSS
/**
 * ========================================
 * THEME BUILDER - Generated CSS Variables
 * ========================================
 * Generated from Operator Panel Theme Builder
 * Do not edit manually - changes will be overwritten
 * Generated at: {$this->getCurrentDateTime()}
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
    --theme-success-light: #e8f8ef;
    --theme-warning-light: #fff8e6;
    --theme-danger-light: #fde8eb;
    --theme-info-light: #e0f2fe;

    /* ===== STATUS HOVER COLORS ===== */
    --theme-success-hover: #1fa058;
    --theme-warning-hover: #e5ad30;
    --theme-danger-hover: #d93a50;
    --theme-info-hover: #0284c7;

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

    /* ===== SPACING ===== */
    --theme-spacing-xs: {$spacingXs};
    --theme-spacing-sm: {$spacingSm};
    --theme-spacing: {$spacing};
    --theme-spacing-lg: {$spacingLg};
    --theme-spacing-xl: {$spacingXl};

    /* ===== BUTTONS ===== */
    --theme-btn-padding-x: {$btnPaddingX};
    --theme-btn-padding-y: {$btnPaddingY};
    --theme-btn-font-size: {$btnFontSize};
    --theme-btn-font-weight: {$btnFontWeight};
    --theme-btn-radius: {$btnRadius};
    --theme-btn-shadow: {$btnShadow};
    --theme-btn-shadow-hover: 0 4px 12px rgba(0,0,0,0.15);
    --theme-btn-transition: all 0.3s ease;
    
    /* Primary Button */
    --theme-btn-primary-bg: {$primary};
    --theme-btn-primary-text: #ffffff;
    --theme-btn-primary-hover-bg: {$primaryHover};
    --theme-btn-primary-hover-text: #ffffff;
    --theme-btn-primary-border: {$primary};
    
    /* Secondary Button */
    --theme-btn-secondary-bg: {$secondary};
    --theme-btn-secondary-text: #ffffff;
    --theme-btn-secondary-hover-bg: {$secondaryHover};
    --theme-btn-secondary-hover-text: #ffffff;
    --theme-btn-secondary-border: {$secondary};
    
    /* Success Button */
    --theme-btn-success-bg: {$success};
    --theme-btn-success-text: #ffffff;
    --theme-btn-success-hover-bg: #1fa058;
    --theme-btn-success-hover-text: #ffffff;
    --theme-btn-success-border: {$success};
    
    /* Danger Button */
    --theme-btn-danger-bg: {$danger};
    --theme-btn-danger-text: #ffffff;
    --theme-btn-danger-hover-bg: #d93a50;
    --theme-btn-danger-hover-text: #ffffff;
    --theme-btn-danger-border: {$danger};
    
    /* Warning Button */
    --theme-btn-warning-bg: {$warning};
    --theme-btn-warning-text: #1f0300;
    --theme-btn-warning-hover-bg: #e5ad30;
    --theme-btn-warning-hover-text: #1f0300;
    --theme-btn-warning-border: {$warning};
    
    /* Info Button */
    --theme-btn-info-bg: {$info};
    --theme-btn-info-text: #ffffff;
    --theme-btn-info-hover-bg: #0284c7;
    --theme-btn-info-hover-text: #ffffff;
    --theme-btn-info-border: {$info};
    
    /* Outline Button */
    --theme-btn-outline-bg: transparent;
    --theme-btn-outline-text: {$textPrimary};
    --theme-btn-outline-border: {$border};
    --theme-btn-outline-hover-bg: {$bgLight};
    --theme-btn-outline-hover-text: {$textPrimary};
    --theme-btn-outline-hover-border: {$borderDark};
    
    /* Ghost Button */
    --theme-btn-ghost-bg: transparent;
    --theme-btn-ghost-text: {$textPrimary};
    --theme-btn-ghost-hover-bg: {$bgLight};
    --theme-btn-ghost-hover-text: {$primary};

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
    --theme-nav-link-active: {$navLinkHover};
    --theme-nav-font-size: {$navFontSize};
    --theme-nav-font-weight: {$navFontWeight};

    /* ===== FOOTER ===== */
    --theme-footer-bg: {$footerBg};
    --theme-footer-text: {$footerText};
    --theme-footer-padding: {$footerPadding};
    --theme-footer-text-muted: {$footerTextMuted};
    --theme-footer-link: {$footerLink};
    --theme-footer-link-hover: {$footerLinkHover};
    --theme-footer-border: {$footerBorder};

    /* ===== ITEM CARDS ===== */
    --theme-catalogItem-name-size: {$itemNameSize};
    --theme-catalogItem-name-weight: {$itemNameWeight};
    --theme-catalogItem-price-size: {$itemPriceSize};
    --theme-catalogItem-price-weight: {$itemPriceWeight};
    --theme-catalogItem-card-radius: {$itemCardRadius};
    --theme-catalogItem-img-radius: {$itemImgRadius};
    --theme-catalogItem-hover-scale: {$itemHoverScale};

    /* ===== MODALS ===== */
    --theme-modal-bg: {$modalBg};
    --theme-modal-radius: {$modalRadius};
    --theme-modal-shadow: {$modalShadow};
    --theme-modal-backdrop: {$modalBackdrop};
    --theme-modal-header-bg: {$modalHeaderBg};

    /* ===== TABLES ===== */
    --theme-table-header-bg: {$tableHeaderBg};
    --theme-table-header-text: {$tableHeaderText};
    --theme-table-border: {$tableBorder};
    --theme-table-hover-bg: {$tableHoverBg};
    --theme-table-stripe-bg: {$tableStripeBg};

    /* ===== FORMS ===== */
    --theme-input-height: {$inputHeight};
    --theme-input-bg: {$inputBg};
    --theme-input-border: {$inputBorder};
    --theme-input-radius: {$inputRadius};
    --theme-input-focus-border: {$inputFocusBorder};
    --theme-input-focus-shadow: {$inputFocusShadow};
    --theme-input-placeholder: {$inputPlaceholder};

    /* ===== BADGES ===== */
    --theme-badge-radius: {$badgeRadius};
    --theme-badge-padding: {$badgePadding};
    --theme-badge-font-size: {$badgeFontSize};
    --theme-badge-font-weight: {$badgeFontWeight};

    /* ===== CHIPS ===== */
    --theme-chip-bg: {$chipBg};
    --theme-chip-text: {$chipText};
    --theme-chip-radius: {$chipRadius};
    --theme-chip-border: {$chipBorder};

    /* ===== SCROLLBAR ===== */
    --theme-scrollbar-width: {$scrollbarWidth};
    --theme-scrollbar-track: {$scrollbarTrack};
    --theme-scrollbar-thumb: {$scrollbarThumb};
    --theme-scrollbar-thumb-hover: {$scrollbarThumbHover};

    /* ===== TRANSITIONS ===== */
    --theme-transition-fast: {$transitionFast};
    --theme-transition: {$transition};
    --theme-transition-slow: {$transitionSlow};

    /* ===== SEARCH COMPONENT ===== */
    --theme-search-bg: {$searchBg};
    --theme-search-border: {$searchBorder};
    --theme-search-radius: {$searchRadius};
    --theme-search-height: {$searchHeight};
    --theme-search-shadow: {$searchShadow};

    /* ===== CATEGORY CARDS ===== */
    --theme-category-bg: {$categoryBg};
    --theme-category-radius: {$categoryRadius};
    --theme-category-shadow: {$categoryShadow};
    --theme-category-hover-shadow: {$categoryHoverShadow};

    /* ===== PAGINATION ===== */
    --theme-pagination-size: {$paginationSize};
    --theme-pagination-radius: {$paginationRadius};
    --theme-pagination-gap: {$paginationGap};

    /* ===== ALERTS ===== */
    --theme-alert-radius: {$alertRadius};
    --theme-alert-padding: {$alertPadding};

    /* ===== BREADCRUMB ===== */
    --theme-breadcrumb-bg: {$breadcrumbBg};
    --theme-breadcrumb-separator: '{$breadcrumbSeparator}';
    --theme-breadcrumb-text: {$breadcrumbText};

    /* ===== SOCIAL COLORS ===== */
    --theme-facebook: {$facebook};
    --theme-twitter: {$twitter};
    --theme-instagram: {$instagram};
    --theme-whatsapp: {$whatsapp};
    --theme-youtube: {$youtube};
    --theme-linkedin: {$linkedin};

    /* ===== LINK COLORS ===== */
    --theme-link: {$primary};
    --theme-link-hover: {$primaryHover};

    /* ===== TOPBAR ===== */
    --theme-topbar-bg: {$secondary};
    --theme-topbar-text: rgba(255, 255, 255, 0.9);
    --theme-topbar-text-hover: #ffffff;

    /* ===== SEMANTIC MAPPING LAYER ===== */
    /* These map semantic names to theme variables for consistency */

    /* Text Semantic */
    --text-primary: var(--theme-text-primary);
    --text-secondary: var(--theme-text-secondary);
    --text-muted: var(--theme-text-muted);
    --text-light: var(--theme-text-light);
    --text-inverse: var(--theme-text-white);
    --text-body: var(--theme-text-primary);
    --text-link: var(--theme-link);
    --text-link-hover: var(--theme-link-hover);

    /* Text-On Colors (WCAG Contrast Safe) */
    --text-on-primary: var(--theme-text-on-primary);
    --text-on-secondary: var(--theme-text-on-secondary);
    --text-on-success: var(--theme-text-on-success);
    --text-on-warning: var(--theme-text-on-warning);
    --text-on-danger: var(--theme-text-on-danger);
    --text-on-info: var(--theme-text-on-info);
    --text-on-dark: var(--theme-text-on-dark);
    --text-on-light: var(--theme-text-on-light);

    /* Text Sizes */
    --text-xs: 11px;
    --text-sm: 13px;
    --text-base: 15px;
    --text-lg: 18px;
    --text-xl: 22px;

    /* Surface/Background Semantic */
    --surface-page: var(--theme-bg-body);
    --surface-card: var(--theme-card-bg);
    --surface-elevated: var(--theme-bg-light);
    --surface-sunken: var(--theme-bg-gray);
    --surface-secondary: var(--theme-secondary-light);

    /* Border Semantic */
    --border-default: var(--theme-border);
    --border-light: var(--theme-border-light);
    --border-strong: var(--theme-border-dark);
    --border-color: var(--theme-border);
    --border-focus: var(--theme-primary);

    /* Action Semantic (for buttons, links, interactive elements) */
    --action-primary: var(--theme-primary);
    --action-primary-hover: var(--theme-primary-hover);
    --action-primary-active: var(--theme-primary-dark);
    --action-secondary: var(--theme-secondary);
    --action-secondary-hover: var(--theme-secondary-hover);
    --action-success: var(--theme-success);
    --action-success-hover: var(--theme-success-hover);
    --action-success-light: var(--theme-success-light);
    --action-warning: var(--theme-warning);
    --action-warning-hover: var(--theme-warning-hover);
    --action-warning-light: var(--theme-warning-light);
    --action-danger: var(--theme-danger);
    --action-danger-hover: var(--theme-danger-hover);
    --action-danger-light: var(--theme-danger-light);
    --action-info: var(--theme-info);
    --action-info-hover: var(--theme-info-hover);
    --action-info-light: var(--theme-info-light);

    /* Radius Semantic */
    --radius-sm: var(--theme-radius-sm);
    --radius-md: var(--theme-radius);
    --radius-lg: var(--theme-radius-lg);
    --radius-full: var(--theme-radius-pill);

    /* Shadow Semantic */
    --shadow-sm: var(--theme-shadow-sm);
    --shadow-md: var(--theme-shadow);
    --shadow-lg: var(--theme-shadow-lg);

    /* Spacing Semantic */
    --space-1: 4px;
    --space-2: 8px;
    --space-3: 12px;
    --space-4: 16px;
    --space-5: 20px;
    --space-6: 24px;
    --space-8: 32px;

    /* Color Semantic Helpers */
    --color-primary-light: var(--theme-primary-light);

    /* ===== LEGACY VARIABLES (Backwards Compatibility) ===== */
    --muaadh-primary: {$primary};
    --muaadh-primary-hover: {$primaryHover};
    --muaadh-primary-dark: {$primaryDark};
    --muaadh-primary-light: {$primaryLight};
    --muaadh-primary-rgb: {$primaryRgb};
    --muaadh-secondary: {$secondary};
    --muaadh-secondary-hover: {$secondaryHover};
    --muaadh-success: {$success};
    --muaadh-warning: {$warning};
    --muaadh-danger: {$danger};
    --muaadh-info: {$info};
    --muaadh-text: {$textPrimary};
    --muaadh-text-muted: {$textMuted};
    --muaadh-border: {$border};
    --muaadh-border-light: {$borderLight};
    --muaadh-bg-light: {$bgLight};
    --muaadh-bg-gray: {$bgGray};
    --muaadh-white: #ffffff;
    --muaadh-dark: {$secondary};
    --muaadh-light: {$bgLight};
    --muaadh-radius: {$radius};
    --muaadh-radius-lg: {$radiusLg};
    --muaadh-shadow-lg: {$shadowLg};
}

/* ===== SCROLLBAR STYLING ===== */
::-webkit-scrollbar {
    width: var(--theme-scrollbar-width);
    height: var(--theme-scrollbar-width);
}

::-webkit-scrollbar-track {
    background: var(--theme-scrollbar-track);
}

::-webkit-scrollbar-thumb {
    background: var(--theme-scrollbar-thumb);
    border-radius: var(--theme-radius);
}

::-webkit-scrollbar-thumb:hover {
    background: var(--theme-scrollbar-thumb-hover);
}

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

        file_put_contents($cssPath, $css);
    }

    /**
     * Get current date time for CSS comment
     */
    private function getCurrentDateTime()
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Convert hex color to RGB
     */
    private function hexToRgb($hex)
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) == 3) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
        return "{$r}, {$g}, {$b}";
    }
}

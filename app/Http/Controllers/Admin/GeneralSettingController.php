<?php

namespace App\Http\Controllers\Admin;

use App\Models\Generalsetting;
use Illuminate\Http\Request;

use Validator;

class GeneralSettingController extends AdminBaseController
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
        $data = Generalsetting::findOrFail(1);
        $data->theme = $request->theme;
        $data->update();
        cache()->forget('generalsettings');
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
        return view('admin.generalsetting.paymentsinfo');
    }

    public function logo()
    {
        return view('admin.generalsetting.logo');
    }

    public function favicon()
    {
        return view('admin.generalsetting.favicon');
    }

    public function loader()
    {
        return view('admin.generalsetting.loader');
    }

    public function websitecontent()
    {
        return view('admin.generalsetting.websitecontent');
    }
    public function popup()
    {
        return view('admin.generalsetting.popup');
    }
    public function breadcrumb()
    {
        return view('admin.generalsetting.breadcrumb');
    }

    public function footer()
    {
        return view('admin.generalsetting.footer');
    }

    public function affilate()
    {
        return view('admin.generalsetting.affilate');
    }

    public function error_banner()
    {
        return view('admin.generalsetting.error_banner');
    }

    public function maintain()
    {
        return view('admin.generalsetting.maintain');
    }

    public function vendor_color()
    {
        return view('admin.generalsetting.vendor_color');
    }

    public function user_image()
    {
        return view('admin.generalsetting.user_image');
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
        else {
            $input = $request->all();
            $data = Generalsetting::findOrFail(1);
            if ($file = $request->file('logo')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->logo);
                $input['logo'] = $name;
            }
            if ($file = $request->file('favicon')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->favicon);
                $input['favicon'] = $name;
            }
            if ($file = $request->file('deal_background')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->favicon);
                $input['deal_background'] = $name;
            }

            if ($file = $request->file('breadcrumb_banner')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->breadcrumb_banner);
                $input['breadcrumb_banner'] = $name;
            }
            if ($file = $request->file('loader')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->loader);
                $input['loader'] = $name;
            }
            if ($file = $request->file('admin_loader')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->admin_loader);
                $input['admin_loader'] = $name;
            }
            if ($file = $request->file('affilate_banner')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->affilate_banner);
                $input['affilate_banner'] = $name;
            }
            if ($file = $request->file('error_banner_404')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->error_banner_404);
                $input['error_banner_404'] = $name;
            }
            if ($file = $request->file('error_banner_500')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->error_banner_500);
                $input['error_banner_500'] = $name;
            }
            if ($file = $request->file('popup_background')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->popup_background);
                $input['popup_background'] = $name;
            }
            if ($file = $request->file('invoice_logo')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->invoice_logo);
                $input['invoice_logo'] = $name;
            }
            if ($file = $request->file('user_image')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->user_image);
                $input['user_image'] = $name;
            }

            if ($file = $request->file('footer_logo')) {
                $name = \PriceHelper::ImageCreateName($file);
                $data->upload($name, $file, $data->footer_logo);
                $input['footer_logo'] = $name;
            }

            if (!empty($request->product_page)) {
                $input['product_page'] = implode(',', $request->product_page);
            } else {
                $input['product_page'] = null;
            }

            if ($request->capcha_secret_key) {
                $this->setEnv('NOCAPTCHA_SECRET', $request->capcha_secret_key, env('NOCAPTCHA_SECRET'));
            }
            if ($request->capcha_site_key) {
                $this->setEnv('NOCAPTCHA_SITEKEY', $request->capcha_site_key, env('NOCAPTCHA_SITEKEY'));
            }

            cache()->forget('generalsettings');
            $data->update($input);
            //--- Logic Section Ends

            //--- Redirect Section
            $msg = __('Data Updated Successfully.');
            return response()->json($msg);
            //--- Redirect Section Ends
        }
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
        else {
            $input = $request->all();
            $data = Generalsetting::findOrFail(1);
            $prev = $data->molly_key;

            if ($request->vendor_ship_info == "") {
                $input['vendor_ship_info'] = 0;
            }

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
            $data->update($input);

            cache()->forget('generalsettings');

            // Set Molly ENV

            //--- Logic Section Ends

            //--- Redirect Section
            $msg = __(__('Data Updated Successfully.'));
            return response()->json($msg);
            //--- Redirect Section Ends
        }
    }

    public function generalMailUpdate(Request $request)
    {
        $input = $request->all();
        $maildata = Generalsetting::findOrFail(1);
        $maildata->update($input);
        //--- Redirect Section
        $msg = 'Mail Data Updated Successfully.';
        return response()->json($msg);
        //--- Redirect Section Ends
    }

    public function isreward($status)
    {
        $data = Generalsetting::findOrFail(1);
        $data->is_reward = $status;
        $data->update();
        cache()->forget('generalsettings');
    }

    // Status Change Method -> GET Request
    public function status($field, $value)
    {
        $prev = '';
        $data = Generalsetting::findOrFail(1);
        if ($field == 'is_debug') {
            $prev = $data->is_debug == 1 ? 'true' : 'false';
        }
        $data[$field] = $value;
        $data->update();
        if ($field == 'is_debug') {
            $now = $data->is_debug == 1 ? 'true' : 'false';
            $this->setEnv('APP_DEBUG', $now, $prev);
        }
        cache()->forget('generalsettings');
        //--- Redirect Section
        $msg = __('Status Updated Successfully.');
        return response()->json($msg);
        //--- Redirect Section Ends

    }

    public function homepage()
    {
        return view('admin.generalsetting.homepage');
    }


    public function otpConfig() {
        return view("admin.generalsetting.otp_config");
    }

    /**
     * Show theme colors settings page
     */
    public function themeColors()
    {
        return view('admin.generalsetting.theme_colors');
    }

    /**
     * Update theme colors - Complete Theme Builder System
     */
    public function updateThemeColors(Request $request)
    {
        $data = Generalsetting::findOrFail(1);

        // ==================================
        // PRIMARY COLORS
        // ==================================
        $data->theme_primary = $request->theme_primary ?? '#c3002f';
        $data->theme_primary_hover = $request->theme_primary_hover ?? '#a00025';
        $data->theme_primary_dark = $request->theme_primary_dark ?? '#8a0020';
        $data->theme_primary_light = $request->theme_primary_light ?? '#fef2f4';

        // ==================================
        // SECONDARY COLORS
        // ==================================
        $data->theme_secondary = $request->theme_secondary ?? '#1f0300';
        $data->theme_secondary_hover = $request->theme_secondary_hover ?? '#351c1a';
        $data->theme_secondary_light = $request->theme_secondary_light ?? '#4c3533';

        // ==================================
        // TEXT COLORS
        // ==================================
        $data->theme_text_primary = $request->theme_text_primary ?? '#1f0300';
        $data->theme_text_secondary = $request->theme_text_secondary ?? '#4c3533';
        $data->theme_text_muted = $request->theme_text_muted ?? '#796866';
        $data->theme_text_light = $request->theme_text_light ?? '#9a8e8c';

        // ==================================
        // BACKGROUND COLORS
        // ==================================
        $data->theme_bg_body = $request->theme_bg_body ?? '#ffffff';
        $data->theme_bg_light = $request->theme_bg_light ?? '#f8f7f7';
        $data->theme_bg_gray = $request->theme_bg_gray ?? '#e9e6e6';
        $data->theme_bg_dark = $request->theme_bg_dark ?? '#030712';

        // ==================================
        // STATUS COLORS
        // ==================================
        $data->theme_success = $request->theme_success ?? '#27be69';
        $data->theme_warning = $request->theme_warning ?? '#fac03c';
        $data->theme_danger = $request->theme_danger ?? '#f2415a';
        $data->theme_info = $request->theme_info ?? '#0ea5e9';

        // ==================================
        // BORDER COLORS
        // ==================================
        $data->theme_border = $request->theme_border ?? '#d9d4d4';
        $data->theme_border_light = $request->theme_border_light ?? '#e9e6e6';
        $data->theme_border_dark = $request->theme_border_dark ?? '#c7c0bf';

        // ==================================
        // HEADER & FOOTER COLORS
        // ==================================
        $data->theme_header_bg = $request->theme_header_bg ?? '#ffffff';
        $data->theme_footer_bg = $request->theme_footer_bg ?? '#030712';
        $data->theme_footer_text = $request->theme_footer_text ?? '#ffffff';
        $data->theme_footer_link_hover = $request->theme_footer_link_hover ?? '#c3002f';

        // ==================================
        // TYPOGRAPHY
        // ==================================
        $data->theme_font_primary = $request->theme_font_primary ?? 'Poppins';
        $data->theme_font_heading = $request->theme_font_heading ?? 'Saira';
        $data->theme_font_size_base = $request->theme_font_size_base ?? '16px';
        $data->theme_font_size_sm = $request->theme_font_size_sm ?? '14px';
        $data->theme_font_size_lg = $request->theme_font_size_lg ?? '18px';
        $data->theme_line_height = $request->theme_line_height ?? '1.5';

        // ==================================
        // BORDER RADIUS
        // ==================================
        $data->theme_radius_xs = $request->theme_radius_xs ?? '3px';
        $data->theme_radius_sm = $request->theme_radius_sm ?? '4px';
        $data->theme_radius = $request->theme_radius ?? '8px';
        $data->theme_radius_lg = $request->theme_radius_lg ?? '12px';
        $data->theme_radius_xl = $request->theme_radius_xl ?? '16px';
        $data->theme_radius_pill = $request->theme_radius_pill ?? '50px';

        // ==================================
        // SHADOWS
        // ==================================
        $data->theme_shadow_xs = $request->theme_shadow_xs ?? '0 1px 2px rgba(0,0,0,0.04)';
        $data->theme_shadow_sm = $request->theme_shadow_sm ?? '0 1px 3px rgba(0,0,0,0.06)';
        $data->theme_shadow = $request->theme_shadow ?? '0 2px 8px rgba(0,0,0,0.1)';
        $data->theme_shadow_lg = $request->theme_shadow_lg ?? '0 4px 16px rgba(0,0,0,0.15)';
        $data->theme_shadow_xl = $request->theme_shadow_xl ?? '0 8px 30px rgba(0,0,0,0.2)';

        // ==================================
        // SPACING
        // ==================================
        $data->theme_spacing_xs = $request->theme_spacing_xs ?? '4px';
        $data->theme_spacing_sm = $request->theme_spacing_sm ?? '8px';
        $data->theme_spacing = $request->theme_spacing ?? '16px';
        $data->theme_spacing_lg = $request->theme_spacing_lg ?? '24px';
        $data->theme_spacing_xl = $request->theme_spacing_xl ?? '32px';

        // ==================================
        // BUTTONS
        // ==================================
        $data->theme_btn_padding_x = $request->theme_btn_padding_x ?? '24px';
        $data->theme_btn_padding_y = $request->theme_btn_padding_y ?? '12px';
        $data->theme_btn_font_size = $request->theme_btn_font_size ?? '14px';
        $data->theme_btn_font_weight = $request->theme_btn_font_weight ?? '600';
        $data->theme_btn_radius = $request->theme_btn_radius ?? '8px';
        $data->theme_btn_shadow = $request->theme_btn_shadow ?? 'none';

        // ==================================
        // CARDS
        // ==================================
        $data->theme_card_bg = $request->theme_card_bg ?? '#ffffff';
        $data->theme_card_border = $request->theme_card_border ?? '#e9e6e6';
        $data->theme_card_radius = $request->theme_card_radius ?? '12px';
        $data->theme_card_shadow = $request->theme_card_shadow ?? '0 2px 8px rgba(0,0,0,0.08)';
        $data->theme_card_hover_shadow = $request->theme_card_hover_shadow ?? '0 4px 16px rgba(0,0,0,0.12)';
        $data->theme_card_padding = $request->theme_card_padding ?? '20px';

        // ==================================
        // HEADER
        // ==================================
        $data->theme_header_height = $request->theme_header_height ?? '80px';
        $data->theme_header_shadow = $request->theme_header_shadow ?? '0 2px 10px rgba(0,0,0,0.1)';
        $data->theme_header_text = $request->theme_header_text ?? '#1f0300';
        $data->theme_nav_link_color = $request->theme_nav_link_color ?? '#1f0300';
        $data->theme_nav_link_hover = $request->theme_nav_link_hover ?? '#c3002f';
        $data->theme_nav_font_size = $request->theme_nav_font_size ?? '15px';
        $data->theme_nav_font_weight = $request->theme_nav_font_weight ?? '500';

        // ==================================
        // FOOTER (Extended)
        // ==================================
        $data->theme_footer_padding = $request->theme_footer_padding ?? '60px';
        $data->theme_footer_text_muted = $request->theme_footer_text_muted ?? '#d9d4d4';
        $data->theme_footer_link = $request->theme_footer_link ?? '#ffffff';
        $data->theme_footer_border = $request->theme_footer_border ?? '#374151';

        // ==================================
        // PRODUCT CARDS
        // ==================================
        $data->theme_product_title_size = $request->theme_product_title_size ?? '14px';
        $data->theme_product_title_weight = $request->theme_product_title_weight ?? '500';
        $data->theme_product_price_size = $request->theme_product_price_size ?? '16px';
        $data->theme_product_price_weight = $request->theme_product_price_weight ?? '700';
        $data->theme_product_card_radius = $request->theme_product_card_radius ?? '12px';
        $data->theme_product_img_radius = $request->theme_product_img_radius ?? '8px';
        $data->theme_product_hover_scale = $request->theme_product_hover_scale ?? '1.02';

        // ==================================
        // MODALS
        // ==================================
        $data->theme_modal_bg = $request->theme_modal_bg ?? '#ffffff';
        $data->theme_modal_radius = $request->theme_modal_radius ?? '16px';
        $data->theme_modal_shadow = $request->theme_modal_shadow ?? '0 25px 50px rgba(0,0,0,0.25)';
        $data->theme_modal_backdrop = $request->theme_modal_backdrop ?? 'rgba(0,0,0,0.5)';
        $data->theme_modal_header_bg = $request->theme_modal_header_bg ?? '#f8f7f7';

        // ==================================
        // TABLES
        // ==================================
        $data->theme_table_header_bg = $request->theme_table_header_bg ?? '#f8f7f7';
        $data->theme_table_header_text = $request->theme_table_header_text ?? '#1f0300';
        $data->theme_table_border = $request->theme_table_border ?? '#e9e6e6';
        $data->theme_table_hover_bg = $request->theme_table_hover_bg ?? '#f8f7f7';
        $data->theme_table_stripe_bg = $request->theme_table_stripe_bg ?? '#fafafa';

        // ==================================
        // FORMS
        // ==================================
        $data->theme_input_height = $request->theme_input_height ?? '48px';
        $data->theme_input_bg = $request->theme_input_bg ?? '#ffffff';
        $data->theme_input_border = $request->theme_input_border ?? '#d9d4d4';
        $data->theme_input_radius = $request->theme_input_radius ?? '8px';
        $data->theme_input_focus_border = $request->theme_input_focus_border ?? '#c3002f';
        $data->theme_input_focus_shadow = $request->theme_input_focus_shadow ?? '0 0 0 3px rgba(195,0,47,0.1)';
        $data->theme_input_placeholder = $request->theme_input_placeholder ?? '#9a8e8c';

        // ==================================
        // BADGES
        // ==================================
        $data->theme_badge_radius = $request->theme_badge_radius ?? '20px';
        $data->theme_badge_padding = $request->theme_badge_padding ?? '4px 12px';
        $data->theme_badge_font_size = $request->theme_badge_font_size ?? '12px';
        $data->theme_badge_font_weight = $request->theme_badge_font_weight ?? '600';

        // ==================================
        // CHIPS
        // ==================================
        $data->theme_chip_bg = $request->theme_chip_bg ?? '#f8f7f7';
        $data->theme_chip_text = $request->theme_chip_text ?? '#4c3533';
        $data->theme_chip_radius = $request->theme_chip_radius ?? '6px';
        $data->theme_chip_border = $request->theme_chip_border ?? '#e9e6e6';

        // ==================================
        // SCROLLBAR
        // ==================================
        $data->theme_scrollbar_width = $request->theme_scrollbar_width ?? '10px';
        $data->theme_scrollbar_track = $request->theme_scrollbar_track ?? '#f1f1f1';
        $data->theme_scrollbar_thumb = $request->theme_scrollbar_thumb ?? '#c1c1c1';
        $data->theme_scrollbar_thumb_hover = $request->theme_scrollbar_thumb_hover ?? '#a1a1a1';

        // ==================================
        // TRANSITIONS
        // ==================================
        $data->theme_transition_fast = $request->theme_transition_fast ?? 'all 0.15s ease';
        $data->theme_transition = $request->theme_transition ?? 'all 0.3s ease';
        $data->theme_transition_slow = $request->theme_transition_slow ?? 'all 0.5s ease';

        // ==================================
        // SEARCH COMPONENT
        // ==================================
        $data->theme_search_bg = $request->theme_search_bg ?? '#ffffff';
        $data->theme_search_border = $request->theme_search_border ?? '#e9e6e6';
        $data->theme_search_radius = $request->theme_search_radius ?? '50px';
        $data->theme_search_height = $request->theme_search_height ?? '50px';
        $data->theme_search_shadow = $request->theme_search_shadow ?? '0 4px 15px rgba(0,0,0,0.08)';

        // ==================================
        // CATEGORY CARDS
        // ==================================
        $data->theme_category_bg = $request->theme_category_bg ?? '#ffffff';
        $data->theme_category_radius = $request->theme_category_radius ?? '12px';
        $data->theme_category_shadow = $request->theme_category_shadow ?? '0 2px 8px rgba(0,0,0,0.08)';
        $data->theme_category_hover_shadow = $request->theme_category_hover_shadow ?? '0 8px 25px rgba(0,0,0,0.15)';

        // ==================================
        // PAGINATION
        // ==================================
        $data->theme_pagination_size = $request->theme_pagination_size ?? '40px';
        $data->theme_pagination_radius = $request->theme_pagination_radius ?? '8px';
        $data->theme_pagination_gap = $request->theme_pagination_gap ?? '5px';

        // ==================================
        // ALERTS
        // ==================================
        $data->theme_alert_radius = $request->theme_alert_radius ?? '8px';
        $data->theme_alert_padding = $request->theme_alert_padding ?? '16px 20px';

        // ==================================
        // BREADCRUMB
        // ==================================
        $data->theme_breadcrumb_bg = $request->theme_breadcrumb_bg ?? '#f8f7f7';
        $data->theme_breadcrumb_separator = $request->theme_breadcrumb_separator ?? '/';
        $data->theme_breadcrumb_text = $request->theme_breadcrumb_text ?? '#796866';

        // ==================================
        // SOCIAL COLORS
        // ==================================
        $data->theme_facebook = $request->theme_facebook ?? '#1877f2';
        $data->theme_twitter = $request->theme_twitter ?? '#1da1f2';
        $data->theme_instagram = $request->theme_instagram ?? '#e4405f';
        $data->theme_whatsapp = $request->theme_whatsapp ?? '#25d366';
        $data->theme_youtube = $request->theme_youtube ?? '#ff0000';
        $data->theme_linkedin = $request->theme_linkedin ?? '#0a66c2';

        $data->save();

        // Clear cache
        cache()->forget('generalsettings');

        // Regenerate CSS file with all theme variables
        $this->generateThemeCss($data);

        return back()->with('success', __('Theme Builder Settings Updated Successfully'));
    }

    /**
     * Generate theme CSS file with all database colors
     * Complete Theme Builder System
     */
    private function generateThemeCss($gs)
    {
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
        // SPACING
        // ==================================
        $spacingXs = $gs->theme_spacing_xs ?? '4px';
        $spacingSm = $gs->theme_spacing_sm ?? '8px';
        $spacing = $gs->theme_spacing ?? '16px';
        $spacingLg = $gs->theme_spacing_lg ?? '24px';
        $spacingXl = $gs->theme_spacing_xl ?? '32px';

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
        // FOOTER (Extended)
        // ==================================
        $footerPadding = $gs->theme_footer_padding ?? '60px';
        $footerTextMuted = $gs->theme_footer_text_muted ?? '#d9d4d4';
        $footerLink = $gs->theme_footer_link ?? '#ffffff';
        $footerBorder = $gs->theme_footer_border ?? '#374151';

        // ==================================
        // PRODUCT CARDS
        // ==================================
        $productTitleSize = $gs->theme_product_title_size ?? '14px';
        $productTitleWeight = $gs->theme_product_title_weight ?? '500';
        $productPriceSize = $gs->theme_product_price_size ?? '16px';
        $productPriceWeight = $gs->theme_product_price_weight ?? '700';
        $productCardRadius = $gs->theme_product_card_radius ?? '12px';
        $productImgRadius = $gs->theme_product_img_radius ?? '8px';
        $productHoverScale = $gs->theme_product_hover_scale ?? '1.02';

        // ==================================
        // MODALS
        // ==================================
        $modalBg = $gs->theme_modal_bg ?? '#ffffff';
        $modalRadius = $gs->theme_modal_radius ?? '16px';
        $modalShadow = $gs->theme_modal_shadow ?? '0 25px 50px rgba(0,0,0,0.25)';
        $modalBackdrop = $gs->theme_modal_backdrop ?? 'rgba(0,0,0,0.5)';
        $modalHeaderBg = $gs->theme_modal_header_bg ?? '#f8f7f7';

        // ==================================
        // TABLES
        // ==================================
        $tableHeaderBg = $gs->theme_table_header_bg ?? '#f8f7f7';
        $tableHeaderText = $gs->theme_table_header_text ?? '#1f0300';
        $tableBorder = $gs->theme_table_border ?? '#e9e6e6';
        $tableHoverBg = $gs->theme_table_hover_bg ?? '#f8f7f7';
        $tableStripeBg = $gs->theme_table_stripe_bg ?? '#fafafa';

        // ==================================
        // FORMS
        // ==================================
        $inputHeight = $gs->theme_input_height ?? '48px';
        $inputBg = $gs->theme_input_bg ?? '#ffffff';
        $inputBorder = $gs->theme_input_border ?? '#d9d4d4';
        $inputRadius = $gs->theme_input_radius ?? '8px';
        $inputFocusBorder = $gs->theme_input_focus_border ?? '#c3002f';
        $inputFocusShadow = $gs->theme_input_focus_shadow ?? '0 0 0 3px rgba(195,0,47,0.1)';
        $inputPlaceholder = $gs->theme_input_placeholder ?? '#9a8e8c';

        // ==================================
        // BADGES
        // ==================================
        $badgeRadius = $gs->theme_badge_radius ?? '20px';
        $badgePadding = $gs->theme_badge_padding ?? '4px 12px';
        $badgeFontSize = $gs->theme_badge_font_size ?? '12px';
        $badgeFontWeight = $gs->theme_badge_font_weight ?? '600';

        // ==================================
        // CHIPS
        // ==================================
        $chipBg = $gs->theme_chip_bg ?? '#f8f7f7';
        $chipText = $gs->theme_chip_text ?? '#4c3533';
        $chipRadius = $gs->theme_chip_radius ?? '6px';
        $chipBorder = $gs->theme_chip_border ?? '#e9e6e6';

        // ==================================
        // SCROLLBAR
        // ==================================
        $scrollbarWidth = $gs->theme_scrollbar_width ?? '10px';
        $scrollbarTrack = $gs->theme_scrollbar_track ?? '#f1f1f1';
        $scrollbarThumb = $gs->theme_scrollbar_thumb ?? '#c1c1c1';
        $scrollbarThumbHover = $gs->theme_scrollbar_thumb_hover ?? '#a1a1a1';

        // ==================================
        // TRANSITIONS
        // ==================================
        $transitionFast = $gs->theme_transition_fast ?? 'all 0.15s ease';
        $transition = $gs->theme_transition ?? 'all 0.3s ease';
        $transitionSlow = $gs->theme_transition_slow ?? 'all 0.5s ease';

        // ==================================
        // SEARCH COMPONENT
        // ==================================
        $searchBg = $gs->theme_search_bg ?? '#ffffff';
        $searchBorder = $gs->theme_search_border ?? '#e9e6e6';
        $searchRadius = $gs->theme_search_radius ?? '50px';
        $searchHeight = $gs->theme_search_height ?? '50px';
        $searchShadow = $gs->theme_search_shadow ?? '0 4px 15px rgba(0,0,0,0.08)';

        // ==================================
        // CATEGORY CARDS
        // ==================================
        $categoryBg = $gs->theme_category_bg ?? '#ffffff';
        $categoryRadius = $gs->theme_category_radius ?? '12px';
        $categoryShadow = $gs->theme_category_shadow ?? '0 2px 8px rgba(0,0,0,0.08)';
        $categoryHoverShadow = $gs->theme_category_hover_shadow ?? '0 8px 25px rgba(0,0,0,0.15)';

        // ==================================
        // PAGINATION
        // ==================================
        $paginationSize = $gs->theme_pagination_size ?? '40px';
        $paginationRadius = $gs->theme_pagination_radius ?? '8px';
        $paginationGap = $gs->theme_pagination_gap ?? '5px';

        // ==================================
        // ALERTS
        // ==================================
        $alertRadius = $gs->theme_alert_radius ?? '8px';
        $alertPadding = $gs->theme_alert_padding ?? '16px 20px';

        // ==================================
        // BREADCRUMB
        // ==================================
        $breadcrumbBg = $gs->theme_breadcrumb_bg ?? '#f8f7f7';
        $breadcrumbSeparator = $gs->theme_breadcrumb_separator ?? '/';
        $breadcrumbText = $gs->theme_breadcrumb_text ?? '#796866';

        // ==================================
        // SOCIAL COLORS
        // ==================================
        $facebook = $gs->theme_facebook ?? '#1877f2';
        $twitter = $gs->theme_twitter ?? '#1da1f2';
        $instagram = $gs->theme_instagram ?? '#e4405f';
        $whatsapp = $gs->theme_whatsapp ?? '#25d366';
        $youtube = $gs->theme_youtube ?? '#ff0000';
        $linkedin = $gs->theme_linkedin ?? '#0a66c2';

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
 * Generated from Admin Panel Theme Builder
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
    --theme-btn-primary-bg: {$primary};
    --theme-btn-primary-hover-bg: {$primaryHover};
    --theme-btn-secondary-bg: {$secondary};
    --theme-btn-secondary-hover-bg: {$secondaryHover};
    --theme-btn-outline-text: #344054;
    --theme-btn-outline-border: {$textLight};

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

    /* ===== PRODUCT CARDS ===== */
    --theme-product-title-size: {$productTitleSize};
    --theme-product-title-weight: {$productTitleWeight};
    --theme-product-price-size: {$productPriceSize};
    --theme-product-price-weight: {$productPriceWeight};
    --theme-product-card-radius: {$productCardRadius};
    --theme-product-img-radius: {$productImgRadius};
    --theme-product-hover-scale: {$productHoverScale};

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

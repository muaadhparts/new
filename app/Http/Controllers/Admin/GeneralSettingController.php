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
     * Update theme colors - Complete unified theme system
     */
    public function updateThemeColors(Request $request)
    {
        $data = Generalsetting::findOrFail(1);

        // Primary Colors
        $data->theme_primary = $request->theme_primary ?? '#c3002f';
        $data->theme_primary_hover = $request->theme_primary_hover ?? '#a00025';
        $data->theme_primary_dark = $request->theme_primary_dark ?? '#8a0020';
        $data->theme_primary_light = $request->theme_primary_light ?? '#fef2f4';

        // Secondary Colors
        $data->theme_secondary = $request->theme_secondary ?? '#1f0300';
        $data->theme_secondary_hover = $request->theme_secondary_hover ?? '#351c1a';
        $data->theme_secondary_light = $request->theme_secondary_light ?? '#4c3533';

        // Text Colors
        $data->theme_text_primary = $request->theme_text_primary ?? '#1f0300';
        $data->theme_text_secondary = $request->theme_text_secondary ?? '#4c3533';
        $data->theme_text_muted = $request->theme_text_muted ?? '#796866';
        $data->theme_text_light = $request->theme_text_light ?? '#9a8e8c';

        // Background Colors
        $data->theme_bg_body = $request->theme_bg_body ?? '#ffffff';
        $data->theme_bg_light = $request->theme_bg_light ?? '#f8f7f7';
        $data->theme_bg_gray = $request->theme_bg_gray ?? '#e9e6e6';
        $data->theme_bg_dark = $request->theme_bg_dark ?? '#030712';

        // Status Colors
        $data->theme_success = $request->theme_success ?? '#27be69';
        $data->theme_warning = $request->theme_warning ?? '#fac03c';
        $data->theme_danger = $request->theme_danger ?? '#f2415a';
        $data->theme_info = $request->theme_info ?? '#0ea5e9';

        // Border Colors
        $data->theme_border = $request->theme_border ?? '#d9d4d4';
        $data->theme_border_light = $request->theme_border_light ?? '#e9e6e6';
        $data->theme_border_dark = $request->theme_border_dark ?? '#c7c0bf';

        // Header & Footer
        $data->theme_header_bg = $request->theme_header_bg ?? '#ffffff';
        $data->theme_footer_bg = $request->theme_footer_bg ?? '#030712';
        $data->theme_footer_text = $request->theme_footer_text ?? '#ffffff';
        $data->theme_footer_link_hover = $request->theme_footer_link_hover ?? '#c3002f';

        $data->save();

        // Clear cache
        cache()->forget('generalsettings');

        // Regenerate CSS file with new colors
        $this->generateThemeCss($data);

        return back()->with('success', __('Theme Colors Updated Successfully'));
    }

    /**
     * Generate theme CSS file with all database colors
     */
    private function generateThemeCss($gs)
    {
        $cssPath = public_path('assets/front/css/theme-colors.css');

        // Get values with defaults
        $primary = $gs->theme_primary ?? '#c3002f';
        $primaryHover = $gs->theme_primary_hover ?? '#a00025';
        $primaryDark = $gs->theme_primary_dark ?? '#8a0020';
        $primaryLight = $gs->theme_primary_light ?? '#fef2f4';

        $secondary = $gs->theme_secondary ?? '#1f0300';
        $secondaryHover = $gs->theme_secondary_hover ?? '#351c1a';
        $secondaryLight = $gs->theme_secondary_light ?? '#4c3533';

        $textPrimary = $gs->theme_text_primary ?? '#1f0300';
        $textSecondary = $gs->theme_text_secondary ?? '#4c3533';
        $textMuted = $gs->theme_text_muted ?? '#796866';
        $textLight = $gs->theme_text_light ?? '#9a8e8c';
        $textLighter = '#b7aead';

        $bgBody = $gs->theme_bg_body ?? '#ffffff';
        $bgLight = $gs->theme_bg_light ?? '#f8f7f7';
        $bgLighter = '#f6f6f6';
        $bgGray = $gs->theme_bg_gray ?? '#e9e6e6';
        $bgDark = $gs->theme_bg_dark ?? '#030712';

        $success = $gs->theme_success ?? '#27be69';
        $warning = $gs->theme_warning ?? '#fac03c';
        $danger = $gs->theme_danger ?? '#f2415a';
        $info = $gs->theme_info ?? '#0ea5e9';

        $border = $gs->theme_border ?? '#d9d4d4';
        $borderLight = $gs->theme_border_light ?? '#e9e6e6';
        $borderDark = $gs->theme_border_dark ?? '#c7c0bf';

        $headerBg = $gs->theme_header_bg ?? '#ffffff';
        $footerBg = $gs->theme_footer_bg ?? '#030712';
        $footerText = $gs->theme_footer_text ?? '#ffffff';
        $footerLinkHover = $gs->theme_footer_link_hover ?? $primary;

        $primaryRgb = $this->hexToRgb($primary);

        $css = <<<CSS
/**
 * THEME COLORS - Generated from Admin Panel
 * This file overrides MUAADH.css :root variables
 * Do not edit manually - changes will be overwritten
 */
:root {
    /* ===== Primary Brand Colors ===== */
    --theme-primary: {$primary};
    --theme-primary-hover: {$primaryHover};
    --theme-primary-dark: {$primaryDark};
    --theme-primary-light: {$primaryLight};
    --theme-primary-rgb: {$primaryRgb};

    /* ===== Secondary Colors ===== */
    --theme-secondary: {$secondary};
    --theme-secondary-hover: {$secondaryHover};
    --theme-secondary-light: {$secondaryLight};

    /* ===== Text Colors ===== */
    --theme-text-primary: {$textPrimary};
    --theme-text-secondary: {$textSecondary};
    --theme-text-muted: {$textMuted};
    --theme-text-light: {$textLight};
    --theme-text-lighter: {$textLighter};
    --theme-text-white: #ffffff;

    /* ===== Background Colors ===== */
    --theme-bg-body: {$bgBody};
    --theme-bg-light: {$bgLight};
    --theme-bg-lighter: {$bgLighter};
    --theme-bg-gray: {$bgGray};
    --theme-bg-dark: {$bgDark};
    --theme-bg-header: {$headerBg};
    --theme-bg-footer: {$footerBg};

    /* ===== Border Colors ===== */
    --theme-border: {$border};
    --theme-border-light: {$borderLight};
    --theme-border-dark: {$borderDark};

    /* ===== Status Colors ===== */
    --theme-success: {$success};
    --theme-warning: {$warning};
    --theme-danger: {$danger};
    --theme-info: {$info};

    /* ===== Button Colors ===== */
    --theme-btn-primary-bg: {$primary};
    --theme-btn-primary-hover-bg: {$primaryHover};
    --theme-btn-secondary-bg: {$secondary};
    --theme-btn-secondary-hover-bg: {$secondaryHover};
    --theme-btn-outline-text: #344054;
    --theme-btn-outline-border: {$textLight};

    /* ===== Link Colors ===== */
    --theme-link: {$primary};
    --theme-link-hover: {$primaryHover};

    /* ===== Header & Navigation ===== */
    --theme-header-bg: {$headerBg};
    --theme-nav-link-hover: {$primary};
    --theme-nav-link-active: {$primary};

    /* ===== Footer ===== */
    --theme-footer-bg: {$footerBg};
    --theme-footer-text: {$footerText};
    --theme-footer-link-hover: {$footerLinkHover};

    /* ===== Scrollbar Colors ===== */
    --theme-scrollbar-track: {$bgLight};
    --theme-scrollbar-thumb: {$borderDark};
    --theme-scrollbar-thumb-hover: {$textLight};

    /* ===== Status Light Colors ===== */
    --theme-success-light: #e8f8ef;
    --theme-warning-light: #fff8e6;
    --theme-danger-light: #fde8eb;
    --theme-info-light: #e0f2fe;

    /* ===== Status Hover Colors ===== */
    --theme-success-hover: #1fa058;
    --theme-warning-hover: #e5ad30;
    --theme-danger-hover: #d93a50;
    --theme-info-hover: #0284c7;

    /* ===== Legacy Variables (Backwards Compatibility) ===== */
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
CSS;

        file_put_contents($cssPath, $css);
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

<?php

namespace App\Http\Controllers\Operator;

use App\Domain\Platform\Models\PlatformSetting;
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
        // Get all theme settings from ThemeService
        $themeSettings = themeService()->getAllAsObject();

        return view('operator.muaadhsetting.theme_colors', [
            'theme' => $themeSettings,
        ]);
    }

    /**
     * Update theme colors - Complete Theme Builder System
     * Delegates to ThemeService (single source of truth)
     */
    public function updateThemeColors(Request $request)
    {
        $themeService = themeService();

        // Get only theme-related inputs from request
        $themeSettings = collect($request->all())
            ->filter(fn($value, $key) => str_starts_with($key, 'theme_') && $value !== null && $value !== '')
            ->toArray();

        // Save all provided theme settings
        $themeService->setMany($themeSettings);

        // Regenerate CSS file
        $themeService->generateCss();

        return back()->with('success', __('Theme Builder Settings Updated Successfully'));
    }

}

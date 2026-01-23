<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use PlatformSetting instead. This model exists for backward compatibility only.
 *
 * MIGRATION PATH:
 * - Read: Use app(PlatformSettingsService::class)->get('key') or $gs from GlobalData
 * - Write: Use PlatformSetting::set('group', 'key', $value)
 *
 * This model will be removed after all controllers are migrated to use PlatformSetting.
 */
class Muaadhsetting extends Model
{
    protected $table = 'muaadhsettings';

    protected $fillable = [
        'logo', 'favicon', 'footer_logo', 'invoice_logo', 'loader', 'admin_loader',
        'user_image', 'error_banner_404', 'error_banner_500', 'affilate_banner',
        'popup_background', 'site_name', 'copyright', 'theme',
        'mail_driver', 'mail_host', 'mail_port', 'mail_encryption', 'mail_user', 'mail_pass',
        'from_email', 'from_name', 'currency_format', 'decimal_separator', 'thousand_separator',
        'is_affilate', 'affilate_charge', 'withdraw_fee', 'withdraw_charge',
        'is_talkto', 'talkto', 'is_popup', 'is_report', 'is_cookie', 'is_buyer_note',
        'is_capcha', 'capcha_site_key', 'capcha_secret_key', 'is_verification_email',
        'is_maintain', 'maintain_text', 'reg_merchant', 'verify_item', 'is_debug',
        'is_currency', 'facebook_pixel', 'is_admin_loader', 'email', 'phone', 'address'
    ];

    /**
     * Image upload helper
     */
    public function upload($name, $file, $oldname)
    {
        $path = public_path('assets/images');

        // Remove old file
        if ($oldname && file_exists($path . '/' . $oldname)) {
            @unlink($path . '/' . $oldname);
        }

        $file->move($path, $name);
    }

    /**
     * Boot method - sync changes to platform_settings
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            cache()->forget('muaadhsettings');
            cache()->forget('core_settings_unified');
            cache()->forget('platform_settings_service_all');
        });
    }
}

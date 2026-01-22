<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Muaadhsetting extends Model
{
    protected $table = 'muaadhsettings';

    protected $fillable = [
        // ==================================
        // BASIC SETTINGS
        // ==================================
        'logo',
        'favicon',
        'site_name',
        'copyright',
        'footer_logo',
        'invoice_logo',

        // ==================================
        // LOADER SETTINGS
        // ==================================
        'loader',
        'admin_loader',
        'is_admin_loader',

        // ==================================
        // CHAT/TALKTO
        // ==================================
        'is_talkto',
        'talkto',

        // ==================================
        // CURRENCY & FORMAT
        // ==================================
        'currency_format',
        'is_currency',
        'decimal_separator',
        'thousand_separator',

        // ==================================
        // WITHDRAW SETTINGS
        // ==================================
        'withdraw_fee',
        'withdraw_charge',

        // ==================================
        // MAIL SETTINGS
        // ==================================
        'mail_driver',
        'mail_host',
        'mail_port',
        'mail_encryption',
        'mail_user',
        'mail_pass',
        'from_email',
        'from_name',

        // ==================================
        // AFFILIATE SETTINGS
        // ==================================
        'is_affilate',
        'affilate_charge',
        'affilate_banner',

        // ==================================
        // SHIPPING SETTINGS
        // ==================================
        'merchant_ship_info',

        // ==================================
        // MERCHANT SETTINGS
        // ==================================
        'reg_merchant',
        'merchant_page_count',

        // ==================================
        // USER SETTINGS
        // ==================================
        'user_image',
        'is_verification_email',
        'is_buyer_note',

        // ==================================
        // WHOLESALE
        // ==================================
        'wholesell',

        // ==================================
        // CAPTCHA
        // ==================================
        'is_capcha',
        'capcha_secret_key',
        'capcha_site_key',

        // ==================================
        // ERROR PAGES
        // ==================================
        'error_banner_404',
        'error_banner_500',

        // ==================================
        // POPUP SETTINGS
        // ==================================
        'is_popup',
        'popup_background',

        // ==================================
        // DISPLAY SETTINGS
        // ==================================
        'show_stock',
        'verify_item',
        'is_report',
        'item_page',

        // ==================================
        // MAINTENANCE
        // ==================================
        'is_maintain',
        'maintain_text',

        // ==================================
        // PAGINATION COUNTS
        // ==================================
        'page_count',
        'favorite_count',

        // ==================================
        // DEBUG & COOKIES
        // ==================================
        'is_debug',
        'is_cookie',

        // ==================================
        // TRACKING
        // ==================================
        'facebook_pixel',
    ];

    public $timestamps = false;

    /**
     * Upload image and delete old one
     */
    public function upload($name, $file, $oldname)
    {
        $file->move('assets/images', $name);
        if ($oldname != null) {
            $oldPath = public_path() . '/assets/images/' . $oldname;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }
    }
}

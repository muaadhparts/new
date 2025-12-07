<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Generalsetting extends Model
{
    protected $fillable = ['logo', 'favicon', 'title','copyright','colors','theme_primary','theme_primary_hover','theme_primary_dark','theme_primary_light','theme_secondary','theme_secondary_hover','theme_secondary_light','theme_text_primary','theme_text_secondary','theme_text_muted','theme_text_light','theme_bg_body','theme_bg_light','theme_bg_gray','theme_bg_dark','theme_success','theme_warning','theme_danger','theme_info','theme_border','theme_border_light','theme_border_dark','theme_header_bg','theme_footer_bg','theme_footer_text','theme_footer_link_hover','loader','admin_loader','talkto','disqus','currency_format','withdraw_fee','withdraw_charge','shipping_cost','mail_driver','mail_host','mail_port','mail_encryption','mail_user','mail_pass','from_email','from_name','is_affilate','affilate_charge','affilate_banner','fixed_commission','percentage_commission','multiple_shipping','vendor_ship_info','is_verification_email','wholesell','is_capcha','error_banner_404','error_banner_500','popup_title','popup_text','popup_background','invoice_logo','user_image','vendor_color','is_secure','paypal_business','footer_logo','paytm_merchant','maintain_text','flash_count','hot_count','new_count','sale_count','best_seller_count','popular_count','top_rated_count','big_save_count','trending_count','page_count','seller_product_count','wishlist_count','vendor_page_count','min_price','max_price','product_page','post_count','wishlist_page','decimal_separator','thousand_separator','version','is_reward','reward_point','reward_dolar','physical','digital','license','affilite','header_color','capcha_secret_key','capcha_site_key','breadcrumb_banner','brand_title','brand_text','deal_title','deal_details','deal_time','deal_background','theme','vonage_key','is_otp','from_number','vonage_secret'];

    public $timestamps = false;

    public function upload($name,$file,$oldname)
    {
        $file->move('assets/images',$name);
        if($oldname != null)
        {
            if (file_exists(public_path().'/assets/images/'.$oldname)) {
                unlink(public_path().'/assets/images/'.$oldname);
            }
        }
    }
}

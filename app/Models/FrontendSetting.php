<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrontendSetting extends Model
{
    protected $table = 'frontend_settings';

    protected $fillable = ['contact_email','street','phone','fax','email','site','best_seller_banner','best_seller_banner_link','big_save_banner','big_save_banner_link','best_seller_banner1','best_seller_banner_link1','big_save_banner1','big_save_banner_link1','brand','bottom_small','rightbanner1','rightbanner2','rightbannerlink1','rightbannerlink2','home','blog','faq','contact','category','featured_promo','our_services','blog','popular_products','third_left_banner','slider','flash_deal','deal_of_the_day','best_sellers','brand','top_big_trending','top_brand','big_save_banner_subtitle','big_save_banner_title','big_save_banner_text','top_banner','large_banner','best_selling','bottom_banner','newsletter'];

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

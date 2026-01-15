<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Muaadhsetting extends Model
{
    protected $table = 'muaadhsettings';

    protected $fillable = [
        // Basic Settings
        'logo', 'favicon', 'site_name', 'copyright', 'colors',

        // ==================================
        // THEME BUILDER - COLOR SYSTEM
        // ==================================
        // Primary Colors
        'theme_primary', 'theme_primary_hover', 'theme_primary_dark', 'theme_primary_light',
        // Secondary Colors
        'theme_secondary', 'theme_secondary_hover', 'theme_secondary_light',
        // Text Colors
        'theme_text_primary', 'theme_text_secondary', 'theme_text_muted', 'theme_text_light',
        // Background Colors
        'theme_bg_body', 'theme_bg_light', 'theme_bg_gray', 'theme_bg_dark',
        // Status Colors
        'theme_success', 'theme_warning', 'theme_danger', 'theme_info',
        // Border Colors
        'theme_border', 'theme_border_light', 'theme_border_dark',
        // Header & Footer Colors
        'theme_header_bg', 'theme_footer_bg', 'theme_footer_text', 'theme_footer_link_hover',

        // ==================================
        // THEME BUILDER - TYPOGRAPHY
        // ==================================
        'theme_font_primary', 'theme_font_heading',
        'theme_font_size_base', 'theme_font_size_sm', 'theme_font_size_lg',
        'theme_line_height',

        // ==================================
        // THEME BUILDER - BORDER RADIUS
        // ==================================
        'theme_radius_xs', 'theme_radius_sm', 'theme_radius',
        'theme_radius_lg', 'theme_radius_xl', 'theme_radius_pill',

        // ==================================
        // THEME BUILDER - SHADOWS
        // ==================================
        'theme_shadow_xs', 'theme_shadow_sm', 'theme_shadow',
        'theme_shadow_lg', 'theme_shadow_xl',

        // ==================================
        // THEME BUILDER - SPACING
        // ==================================
        'theme_spacing_xs', 'theme_spacing_sm', 'theme_spacing',
        'theme_spacing_lg', 'theme_spacing_xl',

        // ==================================
        // THEME BUILDER - BUTTONS
        // ==================================
        'theme_btn_padding_x', 'theme_btn_padding_y', 'theme_btn_font_size',
        'theme_btn_font_weight', 'theme_btn_radius', 'theme_btn_shadow',

        // ==================================
        // THEME BUILDER - CARDS
        // ==================================
        'theme_card_bg', 'theme_card_border', 'theme_card_radius',
        'theme_card_shadow', 'theme_card_hover_shadow', 'theme_card_padding',

        // ==================================
        // THEME BUILDER - HEADER
        // ==================================
        'theme_header_height', 'theme_header_shadow', 'theme_header_text',
        'theme_nav_link_color', 'theme_nav_link_hover',
        'theme_nav_font_size', 'theme_nav_font_weight',

        // ==================================
        // THEME BUILDER - FOOTER (Extended)
        // ==================================
        'theme_footer_padding', 'theme_footer_text_muted',
        'theme_footer_link', 'theme_footer_border',

        // ==================================
        // THEME BUILDER - ITEM CARDS
        // ==================================
        'theme_item_title_size', 'theme_item_title_weight',
        'theme_item_price_size', 'theme_item_price_weight',
        'theme_item_card_radius', 'theme_item_img_radius',
        'theme_item_hover_scale',

        // ==================================
        // THEME BUILDER - MODALS
        // ==================================
        'theme_modal_bg', 'theme_modal_radius', 'theme_modal_shadow',
        'theme_modal_backdrop', 'theme_modal_header_bg',

        // ==================================
        // THEME BUILDER - TABLES
        // ==================================
        'theme_table_header_bg', 'theme_table_header_text',
        'theme_table_border', 'theme_table_hover_bg', 'theme_table_stripe_bg',

        // ==================================
        // THEME BUILDER - FORMS
        // ==================================
        'theme_input_height', 'theme_input_bg', 'theme_input_border',
        'theme_input_radius', 'theme_input_focus_border',
        'theme_input_focus_shadow', 'theme_input_placeholder',

        // ==================================
        // THEME BUILDER - BADGES
        // ==================================
        'theme_badge_radius', 'theme_badge_padding',
        'theme_badge_font_size', 'theme_badge_font_weight',

        // ==================================
        // THEME BUILDER - CHIPS
        // ==================================
        'theme_chip_bg', 'theme_chip_text', 'theme_chip_radius', 'theme_chip_border',

        // ==================================
        // THEME BUILDER - SCROLLBAR
        // ==================================
        'theme_scrollbar_width', 'theme_scrollbar_track',
        'theme_scrollbar_thumb', 'theme_scrollbar_thumb_hover',

        // ==================================
        // THEME BUILDER - TRANSITIONS
        // ==================================
        'theme_transition_fast', 'theme_transition', 'theme_transition_slow',

        // ==================================
        // THEME BUILDER - SEARCH
        // ==================================
        'theme_search_bg', 'theme_search_border', 'theme_search_radius',
        'theme_search_height', 'theme_search_shadow',

        // ==================================
        // THEME BUILDER - CATEGORIES
        // ==================================
        'theme_category_bg', 'theme_category_radius',
        'theme_category_shadow', 'theme_category_hover_shadow',

        // ==================================
        // THEME BUILDER - PAGINATION
        // ==================================
        'theme_pagination_size', 'theme_pagination_radius', 'theme_pagination_gap',

        // ==================================
        // THEME BUILDER - ALERTS
        // ==================================
        'theme_alert_radius', 'theme_alert_padding',

        // ==================================
        // THEME BUILDER - BREADCRUMB
        // ==================================
        'theme_breadcrumb_bg', 'theme_breadcrumb_separator', 'theme_breadcrumb_text',

        // ==================================
        // THEME BUILDER - SOCIAL COLORS
        // ==================================
        'theme_facebook', 'theme_twitter', 'theme_instagram',
        'theme_whatsapp', 'theme_youtube', 'theme_linkedin',

        // ==================================
        // OTHER SETTINGS
        // ==================================
        'loader', 'admin_loader', 'talkto', 'disqus', 'currency_format',
        'withdraw_fee', 'withdraw_charge', 'shipping_cost',
        'mail_driver', 'mail_host', 'mail_port', 'mail_encryption',
        'mail_user', 'mail_pass', 'from_email', 'from_name',
        'is_affilate', 'affilate_charge', 'affilate_banner',
        // Commission is now per-merchant in merchant_commissions table
        'multiple_shipping', 'merchant_ship_info', 'is_verification_email',
        'wholesell', 'is_capcha', 'error_banner_404', 'error_banner_500',
        'popup_title', 'popup_text', 'popup_background',
        'invoice_logo', 'user_image', 'merchant_color', 'is_secure',
        'paypal_business', 'footer_logo', 'paytm_merchant', 'maintain_text',
        'flash_count', 'hot_count', 'new_count', 'sale_count',
        'best_seller_count', 'popular_count', 'top_rated_count',
        'big_save_count', 'trending_count', 'page_count',
        'seller_item_count', 'favorite_count', 'merchant_page_count',
        'item_page', 'post_count', 'favorite_page',
        'decimal_separator', 'thousand_separator', 'version',
        'is_reward', 'reward_point', 'reward_dolar',
        'physical', 'affilite',
        'header_color', 'capcha_secret_key', 'capcha_site_key',
        'brand_title', 'brand_text',
        'deal_title', 'deal_details', 'deal_time', 'deal_background',
        'theme', 'vonage_key', 'is_otp', 'from_number', 'vonage_secret'
    ];

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

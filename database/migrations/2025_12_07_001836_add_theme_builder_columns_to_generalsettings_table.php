<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Theme Builder - Complete Theme Customization System
     */
    public function up(): void
    {
        Schema::table('generalsettings', function (Blueprint $table) {
            // ========================================
            // TYPOGRAPHY SYSTEM
            // ========================================
            $table->string('theme_font_primary', 100)->nullable()->default('Poppins');
            $table->string('theme_font_heading', 100)->nullable()->default('Saira');
            $table->string('theme_font_size_base', 10)->nullable()->default('16px');
            $table->string('theme_font_size_sm', 10)->nullable()->default('14px');
            $table->string('theme_font_size_lg', 10)->nullable()->default('18px');
            $table->string('theme_line_height', 10)->nullable()->default('1.5');

            // ========================================
            // BORDER RADIUS SYSTEM
            // ========================================
            $table->string('theme_radius_xs', 10)->nullable()->default('3px');
            $table->string('theme_radius_sm', 10)->nullable()->default('4px');
            $table->string('theme_radius', 10)->nullable()->default('8px');
            $table->string('theme_radius_lg', 10)->nullable()->default('12px');
            $table->string('theme_radius_xl', 10)->nullable()->default('16px');
            $table->string('theme_radius_pill', 10)->nullable()->default('50px');

            // ========================================
            // SHADOW SYSTEM
            // ========================================
            $table->string('theme_shadow_xs', 100)->nullable()->default('0 1px 2px rgba(0,0,0,0.04)');
            $table->string('theme_shadow_sm', 100)->nullable()->default('0 1px 3px rgba(0,0,0,0.06)');
            $table->string('theme_shadow', 100)->nullable()->default('0 2px 8px rgba(0,0,0,0.1)');
            $table->string('theme_shadow_lg', 100)->nullable()->default('0 4px 16px rgba(0,0,0,0.15)');
            $table->string('theme_shadow_xl', 100)->nullable()->default('0 8px 30px rgba(0,0,0,0.2)');

            // ========================================
            // SPACING SYSTEM
            // ========================================
            $table->string('theme_spacing_xs', 10)->nullable()->default('4px');
            $table->string('theme_spacing_sm', 10)->nullable()->default('8px');
            $table->string('theme_spacing', 10)->nullable()->default('16px');
            $table->string('theme_spacing_lg', 10)->nullable()->default('24px');
            $table->string('theme_spacing_xl', 10)->nullable()->default('32px');

            // ========================================
            // BUTTON SYSTEM
            // ========================================
            $table->string('theme_btn_padding_x', 10)->nullable()->default('24px');
            $table->string('theme_btn_padding_y', 10)->nullable()->default('12px');
            $table->string('theme_btn_font_size', 10)->nullable()->default('14px');
            $table->string('theme_btn_font_weight', 10)->nullable()->default('600');
            $table->string('theme_btn_radius', 10)->nullable()->default('8px');
            $table->string('theme_btn_shadow', 100)->nullable()->default('none');

            // ========================================
            // CARD SYSTEM
            // ========================================
            $table->string('theme_card_bg', 20)->nullable()->default('#ffffff');
            $table->string('theme_card_border', 20)->nullable()->default('#e9e6e6');
            $table->string('theme_card_radius', 10)->nullable()->default('12px');
            $table->string('theme_card_shadow', 100)->nullable()->default('0 2px 8px rgba(0,0,0,0.08)');
            $table->string('theme_card_hover_shadow', 100)->nullable()->default('0 4px 16px rgba(0,0,0,0.12)');
            $table->string('theme_card_padding', 10)->nullable()->default('20px');

            // ========================================
            // HEADER SYSTEM
            // ========================================
            $table->string('theme_header_height', 10)->nullable()->default('80px');
            $table->string('theme_header_shadow', 100)->nullable()->default('0 2px 10px rgba(0,0,0,0.1)');
            $table->string('theme_header_text', 20)->nullable()->default('#1f0300');
            $table->string('theme_nav_link_color', 20)->nullable()->default('#1f0300');
            $table->string('theme_nav_link_hover', 20)->nullable()->default('#c3002f');
            $table->string('theme_nav_font_size', 10)->nullable()->default('15px');
            $table->string('theme_nav_font_weight', 10)->nullable()->default('500');

            // ========================================
            // FOOTER SYSTEM (Extended)
            // ========================================
            $table->string('theme_footer_padding', 10)->nullable()->default('60px');
            $table->string('theme_footer_text_muted', 20)->nullable()->default('#d9d4d4');
            $table->string('theme_footer_link', 20)->nullable()->default('#ffffff');
            $table->string('theme_footer_border', 20)->nullable()->default('#374151');

            // ========================================
            // PRODUCT CARD SYSTEM
            // ========================================
            $table->string('theme_product_title_size', 10)->nullable()->default('14px');
            $table->string('theme_product_title_weight', 10)->nullable()->default('500');
            $table->string('theme_product_price_size', 10)->nullable()->default('16px');
            $table->string('theme_product_price_weight', 10)->nullable()->default('700');
            $table->string('theme_product_card_radius', 10)->nullable()->default('12px');
            $table->string('theme_product_img_radius', 10)->nullable()->default('8px');
            $table->string('theme_product_hover_scale', 10)->nullable()->default('1.02');

            // ========================================
            // MODAL SYSTEM
            // ========================================
            $table->string('theme_modal_bg', 20)->nullable()->default('#ffffff');
            $table->string('theme_modal_radius', 10)->nullable()->default('16px');
            $table->string('theme_modal_shadow', 100)->nullable()->default('0 25px 50px rgba(0,0,0,0.25)');
            $table->string('theme_modal_backdrop', 50)->nullable()->default('rgba(0,0,0,0.5)');
            $table->string('theme_modal_header_bg', 20)->nullable()->default('#f8f7f7');

            // ========================================
            // TABLE SYSTEM
            // ========================================
            $table->string('theme_table_header_bg', 20)->nullable()->default('#f8f7f7');
            $table->string('theme_table_header_text', 20)->nullable()->default('#1f0300');
            $table->string('theme_table_border', 20)->nullable()->default('#e9e6e6');
            $table->string('theme_table_hover_bg', 20)->nullable()->default('#f8f7f7');
            $table->string('theme_table_stripe_bg', 20)->nullable()->default('#fafafa');

            // ========================================
            // FORM SYSTEM
            // ========================================
            $table->string('theme_input_height', 10)->nullable()->default('48px');
            $table->string('theme_input_bg', 20)->nullable()->default('#ffffff');
            $table->string('theme_input_border', 20)->nullable()->default('#d9d4d4');
            $table->string('theme_input_radius', 10)->nullable()->default('8px');
            $table->string('theme_input_focus_border', 20)->nullable()->default('#c3002f');
            $table->string('theme_input_focus_shadow', 100)->nullable()->default('0 0 0 3px rgba(195,0,47,0.1)');
            $table->string('theme_input_placeholder', 20)->nullable()->default('#9a8e8c');

            // ========================================
            // BADGE SYSTEM
            // ========================================
            $table->string('theme_badge_radius', 10)->nullable()->default('20px');
            $table->string('theme_badge_padding', 20)->nullable()->default('4px 12px');
            $table->string('theme_badge_font_size', 10)->nullable()->default('12px');
            $table->string('theme_badge_font_weight', 10)->nullable()->default('600');

            // ========================================
            // CHIP SYSTEM
            // ========================================
            $table->string('theme_chip_bg', 20)->nullable()->default('#f8f7f7');
            $table->string('theme_chip_text', 20)->nullable()->default('#4c3533');
            $table->string('theme_chip_radius', 10)->nullable()->default('6px');
            $table->string('theme_chip_border', 20)->nullable()->default('#e9e6e6');

            // ========================================
            // SCROLLBAR SYSTEM
            // ========================================
            $table->string('theme_scrollbar_width', 10)->nullable()->default('10px');
            $table->string('theme_scrollbar_track', 20)->nullable()->default('#f1f1f1');
            $table->string('theme_scrollbar_thumb', 20)->nullable()->default('#c1c1c1');
            $table->string('theme_scrollbar_thumb_hover', 20)->nullable()->default('#a1a1a1');

            // ========================================
            // TRANSITION SYSTEM
            // ========================================
            $table->string('theme_transition_fast', 50)->nullable()->default('all 0.15s ease');
            $table->string('theme_transition', 50)->nullable()->default('all 0.3s ease');
            $table->string('theme_transition_slow', 50)->nullable()->default('all 0.5s ease');

            // ========================================
            // SEARCH COMPONENT
            // ========================================
            $table->string('theme_search_bg', 20)->nullable()->default('#ffffff');
            $table->string('theme_search_border', 20)->nullable()->default('#e9e6e6');
            $table->string('theme_search_radius', 10)->nullable()->default('50px');
            $table->string('theme_search_height', 10)->nullable()->default('50px');
            $table->string('theme_search_shadow', 100)->nullable()->default('0 4px 15px rgba(0,0,0,0.08)');

            // ========================================
            // CATEGORY CARD SYSTEM
            // ========================================
            $table->string('theme_category_bg', 20)->nullable()->default('#ffffff');
            $table->string('theme_category_radius', 10)->nullable()->default('12px');
            $table->string('theme_category_shadow', 100)->nullable()->default('0 2px 8px rgba(0,0,0,0.08)');
            $table->string('theme_category_hover_shadow', 100)->nullable()->default('0 8px 25px rgba(0,0,0,0.15)');

            // ========================================
            // PAGINATION SYSTEM
            // ========================================
            $table->string('theme_pagination_size', 10)->nullable()->default('40px');
            $table->string('theme_pagination_radius', 10)->nullable()->default('8px');
            $table->string('theme_pagination_gap', 10)->nullable()->default('5px');

            // ========================================
            // ALERT SYSTEM
            // ========================================
            $table->string('theme_alert_radius', 10)->nullable()->default('8px');
            $table->string('theme_alert_padding', 20)->nullable()->default('16px 20px');

            // ========================================
            // BREADCRUMB SYSTEM
            // ========================================
            $table->string('theme_breadcrumb_bg', 20)->nullable()->default('#f8f7f7');
            $table->string('theme_breadcrumb_separator', 20)->nullable()->default('/');
            $table->string('theme_breadcrumb_text', 20)->nullable()->default('#796866');

            // ========================================
            // SOCIAL COLORS (Fixed Brand Colors)
            // ========================================
            $table->string('theme_facebook', 20)->nullable()->default('#1877f2');
            $table->string('theme_twitter', 20)->nullable()->default('#1da1f2');
            $table->string('theme_instagram', 20)->nullable()->default('#e4405f');
            $table->string('theme_whatsapp', 20)->nullable()->default('#25d366');
            $table->string('theme_youtube', 20)->nullable()->default('#ff0000');
            $table->string('theme_linkedin', 20)->nullable()->default('#0a66c2');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('generalsettings', function (Blueprint $table) {
            // Typography
            $table->dropColumn([
                'theme_font_primary', 'theme_font_heading', 'theme_font_size_base',
                'theme_font_size_sm', 'theme_font_size_lg', 'theme_line_height'
            ]);

            // Border Radius
            $table->dropColumn([
                'theme_radius_xs', 'theme_radius_sm', 'theme_radius',
                'theme_radius_lg', 'theme_radius_xl', 'theme_radius_pill'
            ]);

            // Shadows
            $table->dropColumn([
                'theme_shadow_xs', 'theme_shadow_sm', 'theme_shadow',
                'theme_shadow_lg', 'theme_shadow_xl'
            ]);

            // Spacing
            $table->dropColumn([
                'theme_spacing_xs', 'theme_spacing_sm', 'theme_spacing',
                'theme_spacing_lg', 'theme_spacing_xl'
            ]);

            // Buttons
            $table->dropColumn([
                'theme_btn_padding_x', 'theme_btn_padding_y', 'theme_btn_font_size',
                'theme_btn_font_weight', 'theme_btn_radius', 'theme_btn_shadow'
            ]);

            // Cards
            $table->dropColumn([
                'theme_card_bg', 'theme_card_border', 'theme_card_radius',
                'theme_card_shadow', 'theme_card_hover_shadow', 'theme_card_padding'
            ]);

            // Header
            $table->dropColumn([
                'theme_header_height', 'theme_header_shadow', 'theme_header_text',
                'theme_nav_link_color', 'theme_nav_link_hover', 'theme_nav_font_size',
                'theme_nav_font_weight'
            ]);

            // Footer Extended
            $table->dropColumn([
                'theme_footer_padding', 'theme_footer_text_muted',
                'theme_footer_link', 'theme_footer_border'
            ]);

            // Product Cards
            $table->dropColumn([
                'theme_product_title_size', 'theme_product_title_weight',
                'theme_product_price_size', 'theme_product_price_weight',
                'theme_product_card_radius', 'theme_product_img_radius',
                'theme_product_hover_scale'
            ]);

            // Modals
            $table->dropColumn([
                'theme_modal_bg', 'theme_modal_radius', 'theme_modal_shadow',
                'theme_modal_backdrop', 'theme_modal_header_bg'
            ]);

            // Tables
            $table->dropColumn([
                'theme_table_header_bg', 'theme_table_header_text',
                'theme_table_border', 'theme_table_hover_bg', 'theme_table_stripe_bg'
            ]);

            // Forms
            $table->dropColumn([
                'theme_input_height', 'theme_input_bg', 'theme_input_border',
                'theme_input_radius', 'theme_input_focus_border',
                'theme_input_focus_shadow', 'theme_input_placeholder'
            ]);

            // Badges
            $table->dropColumn([
                'theme_badge_radius', 'theme_badge_padding',
                'theme_badge_font_size', 'theme_badge_font_weight'
            ]);

            // Chips
            $table->dropColumn([
                'theme_chip_bg', 'theme_chip_text',
                'theme_chip_radius', 'theme_chip_border'
            ]);

            // Scrollbar
            $table->dropColumn([
                'theme_scrollbar_width', 'theme_scrollbar_track',
                'theme_scrollbar_thumb', 'theme_scrollbar_thumb_hover'
            ]);

            // Transitions
            $table->dropColumn([
                'theme_transition_fast', 'theme_transition', 'theme_transition_slow'
            ]);

            // Search
            $table->dropColumn([
                'theme_search_bg', 'theme_search_border', 'theme_search_radius',
                'theme_search_height', 'theme_search_shadow'
            ]);

            // Category Cards
            $table->dropColumn([
                'theme_category_bg', 'theme_category_radius',
                'theme_category_shadow', 'theme_category_hover_shadow'
            ]);

            // Pagination
            $table->dropColumn([
                'theme_pagination_size', 'theme_pagination_radius', 'theme_pagination_gap'
            ]);

            // Alerts
            $table->dropColumn(['theme_alert_radius', 'theme_alert_padding']);

            // Breadcrumb
            $table->dropColumn([
                'theme_breadcrumb_bg', 'theme_breadcrumb_separator', 'theme_breadcrumb_text'
            ]);

            // Social
            $table->dropColumn([
                'theme_facebook', 'theme_twitter', 'theme_instagram',
                'theme_whatsapp', 'theme_youtube', 'theme_linkedin'
            ]);
        });
    }
};

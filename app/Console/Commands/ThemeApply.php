<?php

namespace App\Console\Commands;

use App\Models\Muaadhsetting;
use Illuminate\Console\Command;

class ThemeApply extends Command
{
    protected $signature = 'theme:apply {preset : Preset name (nissan, blue, green, purple, orange, teal, gold, rose, ocean, forest, midnight, crimson)}
                            {--generate-css : Also regenerate theme-colors.css}
                            {--run-audit : Run contrast audit after applying}';

    protected $description = 'Apply a theme preset and optionally regenerate theme-colors.css';

    /**
     * Complete theme presets matching Theme Builder
     */
    private $presets = [
        'nissan' => [
            'theme_primary' => '#c3002f', 'theme_primary_hover' => '#a00025', 'theme_primary_dark' => '#8a0020', 'theme_primary_light' => '#fef2f4',
            'theme_secondary' => '#1f2937', 'theme_secondary_hover' => '#374151', 'theme_secondary_light' => '#4b5563',
            'theme_text_primary' => '#111827', 'theme_text_secondary' => '#374151', 'theme_text_muted' => '#6b7280', 'theme_text_light' => '#9ca3af',
            'theme_bg_body' => '#ffffff', 'theme_bg_light' => '#f9fafb', 'theme_bg_gray' => '#f3f4f6', 'theme_bg_dark' => '#111827',
            'theme_success' => '#10b981', 'theme_warning' => '#f59e0b', 'theme_danger' => '#ef4444', 'theme_info' => '#3b82f6',
            'theme_border' => '#e5e7eb', 'theme_border_light' => '#f3f4f6', 'theme_border_dark' => '#d1d5db',
            'theme_font_primary' => 'Poppins', 'theme_font_heading' => 'Poppins',
            'theme_font_size_base' => '15px', 'theme_font_size_sm' => '13px', 'theme_font_size_lg' => '18px',
            'theme_radius_xs' => '2px', 'theme_radius_sm' => '4px', 'theme_radius' => '8px', 'theme_radius_lg' => '10px', 'theme_radius_xl' => '12px', 'theme_radius_pill' => '50px',
            'theme_shadow_sm' => '0 1px 2px rgba(0,0,0,0.05)', 'theme_shadow' => '0 2px 8px rgba(0,0,0,0.08)', 'theme_shadow_lg' => '0 4px 16px rgba(0,0,0,0.12)',
            'theme_btn_padding_x' => '20px', 'theme_btn_padding_y' => '10px', 'theme_btn_font_size' => '14px', 'theme_btn_font_weight' => '600', 'theme_btn_radius' => '6px', 'theme_btn_shadow' => 'none',
            'theme_card_bg' => '#ffffff', 'theme_card_border' => '#e5e7eb', 'theme_card_radius' => '10px', 'theme_card_shadow' => '0 1px 4px rgba(0,0,0,0.06)', 'theme_card_hover_shadow' => '0 4px 12px rgba(0,0,0,0.1)', 'theme_card_padding' => '20px',
            'theme_item_title_size' => '14px', 'theme_item_title_weight' => '500', 'theme_item_price_size' => '16px', 'theme_item_hover_scale' => '1.02',
            'theme_input_height' => '44px', 'theme_input_bg' => '#ffffff', 'theme_input_border' => '#d1d5db', 'theme_input_radius' => '6px', 'theme_input_focus_border' => '#c3002f', 'theme_input_focus_shadow' => '0 0 0 3px rgba(195,0,47,0.1)', 'theme_input_placeholder' => '#9ca3af',
            'theme_header_bg' => '#ffffff', 'theme_header_height' => '70px', 'theme_header_shadow' => '0 1px 3px rgba(0,0,0,0.08)',
            'theme_nav_link_color' => '#374151', 'theme_nav_link_hover' => '#c3002f', 'theme_nav_font_size' => '15px', 'theme_nav_font_weight' => '500',
            'theme_footer_bg' => '#111827', 'theme_footer_text' => '#f9fafb', 'theme_footer_text_muted' => '#9ca3af', 'theme_footer_link_hover' => '#c3002f', 'theme_footer_padding' => '50px', 'theme_footer_link' => '#e5e7eb', 'theme_footer_border' => '#374151',
            'theme_badge_radius' => '4px', 'theme_badge_padding' => '4px 10px', 'theme_badge_font_size' => '12px', 'theme_badge_font_weight' => '600',
            'theme_scrollbar_width' => '8px', 'theme_scrollbar_track' => '#f1f5f9', 'theme_scrollbar_thumb' => '#cbd5e1', 'theme_scrollbar_thumb_hover' => '#94a3b8',
            'theme_modal_bg' => '#ffffff', 'theme_modal_radius' => '12px', 'theme_modal_backdrop' => 'rgba(0,0,0,0.5)',
            'theme_table_header_bg' => '#f9fafb', 'theme_table_border' => '#e5e7eb', 'theme_table_hover_bg' => '#f9fafb'
        ],

        'blue' => [
            'theme_primary' => '#2563eb', 'theme_primary_hover' => '#1d4ed8', 'theme_primary_dark' => '#1e40af', 'theme_primary_light' => '#eff6ff',
            'theme_secondary' => '#1e293b', 'theme_secondary_hover' => '#334155', 'theme_secondary_light' => '#475569',
            'theme_text_primary' => '#0f172a', 'theme_text_secondary' => '#334155', 'theme_text_muted' => '#64748b', 'theme_text_light' => '#94a3b8',
            'theme_bg_body' => '#ffffff', 'theme_bg_light' => '#f8fafc', 'theme_bg_gray' => '#f1f5f9', 'theme_bg_dark' => '#0f172a',
            'theme_success' => '#10b981', 'theme_warning' => '#f59e0b', 'theme_danger' => '#ef4444', 'theme_info' => '#06b6d4',
            'theme_border' => '#e2e8f0', 'theme_border_light' => '#f1f5f9', 'theme_border_dark' => '#cbd5e1',
            'theme_font_primary' => 'Inter', 'theme_font_heading' => 'Inter',
            'theme_font_size_base' => '15px', 'theme_font_size_sm' => '13px', 'theme_font_size_lg' => '18px',
            'theme_radius_xs' => '4px', 'theme_radius_sm' => '6px', 'theme_radius' => '10px', 'theme_radius_lg' => '14px', 'theme_radius_xl' => '18px', 'theme_radius_pill' => '9999px',
            'theme_shadow_sm' => '0 1px 2px rgba(0,0,0,0.04)', 'theme_shadow' => '0 4px 6px rgba(0,0,0,0.07)', 'theme_shadow_lg' => '0 10px 25px rgba(0,0,0,0.1)',
            'theme_btn_padding_x' => '22px', 'theme_btn_padding_y' => '11px', 'theme_btn_font_size' => '14px', 'theme_btn_font_weight' => '500', 'theme_btn_radius' => '8px', 'theme_btn_shadow' => '0 1px 2px rgba(0,0,0,0.05)',
            'theme_card_bg' => '#ffffff', 'theme_card_border' => '#e2e8f0', 'theme_card_radius' => '14px', 'theme_card_shadow' => '0 1px 3px rgba(0,0,0,0.05)', 'theme_card_hover_shadow' => '0 8px 20px rgba(0,0,0,0.08)', 'theme_card_padding' => '22px',
            'theme_item_title_size' => '14px', 'theme_item_title_weight' => '500', 'theme_item_price_size' => '16px', 'theme_item_hover_scale' => '1.03',
            'theme_input_height' => '46px', 'theme_input_bg' => '#ffffff', 'theme_input_border' => '#e2e8f0', 'theme_input_radius' => '8px', 'theme_input_focus_border' => '#2563eb', 'theme_input_focus_shadow' => '0 0 0 3px rgba(37,99,235,0.12)', 'theme_input_placeholder' => '#94a3b8',
            'theme_header_bg' => '#ffffff', 'theme_header_height' => '68px', 'theme_header_shadow' => '0 1px 3px rgba(0,0,0,0.06)',
            'theme_nav_link_color' => '#334155', 'theme_nav_link_hover' => '#2563eb', 'theme_nav_font_size' => '15px', 'theme_nav_font_weight' => '500',
            'theme_footer_bg' => '#0f172a', 'theme_footer_text' => '#f1f5f9', 'theme_footer_text_muted' => '#94a3b8', 'theme_footer_link_hover' => '#60a5fa', 'theme_footer_padding' => '55px', 'theme_footer_link' => '#cbd5e1', 'theme_footer_border' => '#334155',
            'theme_badge_radius' => '6px', 'theme_badge_padding' => '4px 12px', 'theme_badge_font_size' => '12px', 'theme_badge_font_weight' => '500',
            'theme_scrollbar_width' => '8px', 'theme_scrollbar_track' => '#f1f5f9', 'theme_scrollbar_thumb' => '#cbd5e1', 'theme_scrollbar_thumb_hover' => '#94a3b8',
            'theme_modal_bg' => '#ffffff', 'theme_modal_radius' => '16px', 'theme_modal_backdrop' => 'rgba(15,23,42,0.6)',
            'theme_table_header_bg' => '#f8fafc', 'theme_table_border' => '#e2e8f0', 'theme_table_hover_bg' => '#f8fafc'
        ],

        'green' => [
            'theme_primary' => '#059669', 'theme_primary_hover' => '#047857', 'theme_primary_dark' => '#065f46', 'theme_primary_light' => '#ecfdf5',
            'theme_secondary' => '#1f2937', 'theme_secondary_hover' => '#374151', 'theme_secondary_light' => '#4b5563',
            'theme_text_primary' => '#111827', 'theme_text_secondary' => '#374151', 'theme_text_muted' => '#6b7280', 'theme_text_light' => '#9ca3af',
            'theme_bg_body' => '#ffffff', 'theme_bg_light' => '#f9fafb', 'theme_bg_gray' => '#f0fdf4', 'theme_bg_dark' => '#064e3b',
            'theme_success' => '#10b981', 'theme_warning' => '#f59e0b', 'theme_danger' => '#ef4444', 'theme_info' => '#0891b2',
            'theme_border' => '#d1fae5', 'theme_border_light' => '#ecfdf5', 'theme_border_dark' => '#a7f3d0',
            'theme_font_primary' => 'Nunito', 'theme_font_heading' => 'Nunito',
            'theme_font_size_base' => '15px', 'theme_font_size_sm' => '13px', 'theme_font_size_lg' => '18px',
            'theme_radius_xs' => '4px', 'theme_radius_sm' => '6px', 'theme_radius' => '12px', 'theme_radius_lg' => '16px', 'theme_radius_xl' => '20px', 'theme_radius_pill' => '9999px',
            'theme_shadow_sm' => '0 1px 2px rgba(5,150,105,0.06)', 'theme_shadow' => '0 4px 12px rgba(5,150,105,0.08)', 'theme_shadow_lg' => '0 8px 24px rgba(5,150,105,0.12)',
            'theme_btn_padding_x' => '24px', 'theme_btn_padding_y' => '12px', 'theme_btn_font_size' => '14px', 'theme_btn_font_weight' => '600', 'theme_btn_radius' => '10px', 'theme_btn_shadow' => 'none',
            'theme_card_bg' => '#ffffff', 'theme_card_border' => '#d1fae5', 'theme_card_radius' => '16px', 'theme_card_shadow' => '0 2px 8px rgba(5,150,105,0.06)', 'theme_card_hover_shadow' => '0 8px 24px rgba(5,150,105,0.1)', 'theme_card_padding' => '22px',
            'theme_item_title_size' => '14px', 'theme_item_title_weight' => '500', 'theme_item_price_size' => '16px', 'theme_item_hover_scale' => '1.02',
            'theme_input_height' => '48px', 'theme_input_bg' => '#ffffff', 'theme_input_border' => '#d1fae5', 'theme_input_radius' => '10px', 'theme_input_focus_border' => '#059669', 'theme_input_focus_shadow' => '0 0 0 3px rgba(5,150,105,0.15)', 'theme_input_placeholder' => '#9ca3af',
            'theme_header_bg' => '#ffffff', 'theme_header_height' => '70px', 'theme_header_shadow' => '0 1px 3px rgba(5,150,105,0.08)',
            'theme_nav_link_color' => '#374151', 'theme_nav_link_hover' => '#059669', 'theme_nav_font_size' => '15px', 'theme_nav_font_weight' => '500',
            'theme_footer_bg' => '#064e3b', 'theme_footer_text' => '#ecfdf5', 'theme_footer_text_muted' => '#a7f3d0', 'theme_footer_link_hover' => '#34d399', 'theme_footer_padding' => '55px', 'theme_footer_link' => '#d1fae5', 'theme_footer_border' => '#047857',
            'theme_badge_radius' => '8px', 'theme_badge_padding' => '5px 12px', 'theme_badge_font_size' => '12px', 'theme_badge_font_weight' => '600',
            'theme_scrollbar_width' => '8px', 'theme_scrollbar_track' => '#ecfdf5', 'theme_scrollbar_thumb' => '#a7f3d0', 'theme_scrollbar_thumb_hover' => '#6ee7b7',
            'theme_modal_bg' => '#ffffff', 'theme_modal_radius' => '18px', 'theme_modal_backdrop' => 'rgba(6,78,59,0.5)',
            'theme_table_header_bg' => '#ecfdf5', 'theme_table_border' => '#d1fae5', 'theme_table_hover_bg' => '#f0fdf4'
        ],

        'purple' => [
            'theme_primary' => '#7c3aed', 'theme_primary_hover' => '#6d28d9', 'theme_primary_dark' => '#5b21b6', 'theme_primary_light' => '#f5f3ff',
            'theme_secondary' => '#1f2937', 'theme_secondary_hover' => '#374151', 'theme_secondary_light' => '#4b5563',
            'theme_text_primary' => '#111827', 'theme_text_secondary' => '#374151', 'theme_text_muted' => '#6b7280', 'theme_text_light' => '#9ca3af',
            'theme_bg_body' => '#ffffff', 'theme_bg_light' => '#faf8ff', 'theme_bg_gray' => '#f5f3ff', 'theme_bg_dark' => '#1e1b4b',
            'theme_success' => '#10b981', 'theme_warning' => '#f59e0b', 'theme_danger' => '#ef4444', 'theme_info' => '#06b6d4',
            'theme_border' => '#e9d5ff', 'theme_border_light' => '#f5f3ff', 'theme_border_dark' => '#d8b4fe',
            'theme_font_primary' => 'Poppins', 'theme_font_heading' => 'Playfair Display',
            'theme_font_size_base' => '15px', 'theme_font_size_sm' => '13px', 'theme_font_size_lg' => '19px',
            'theme_radius_xs' => '6px', 'theme_radius_sm' => '8px', 'theme_radius' => '14px', 'theme_radius_lg' => '18px', 'theme_radius_xl' => '24px', 'theme_radius_pill' => '9999px',
            'theme_shadow_sm' => '0 1px 3px rgba(124,58,237,0.08)', 'theme_shadow' => '0 4px 16px rgba(124,58,237,0.1)', 'theme_shadow_lg' => '0 12px 32px rgba(124,58,237,0.15)',
            'theme_btn_padding_x' => '26px', 'theme_btn_padding_y' => '13px', 'theme_btn_font_size' => '14px', 'theme_btn_font_weight' => '500', 'theme_btn_radius' => '12px', 'theme_btn_shadow' => '0 2px 6px rgba(124,58,237,0.2)',
            'theme_card_bg' => '#ffffff', 'theme_card_border' => '#e9d5ff', 'theme_card_radius' => '18px', 'theme_card_shadow' => '0 2px 12px rgba(124,58,237,0.08)', 'theme_card_hover_shadow' => '0 12px 32px rgba(124,58,237,0.15)', 'theme_card_padding' => '24px',
            'theme_item_title_size' => '14px', 'theme_item_title_weight' => '500', 'theme_item_price_size' => '17px', 'theme_item_hover_scale' => '1.03',
            'theme_input_height' => '50px', 'theme_input_bg' => '#ffffff', 'theme_input_border' => '#e9d5ff', 'theme_input_radius' => '12px', 'theme_input_focus_border' => '#7c3aed', 'theme_input_focus_shadow' => '0 0 0 4px rgba(124,58,237,0.12)', 'theme_input_placeholder' => '#a1a1aa',
            'theme_header_bg' => '#ffffff', 'theme_header_height' => '72px', 'theme_header_shadow' => '0 2px 8px rgba(124,58,237,0.08)',
            'theme_nav_link_color' => '#374151', 'theme_nav_link_hover' => '#7c3aed', 'theme_nav_font_size' => '15px', 'theme_nav_font_weight' => '500',
            'theme_footer_bg' => '#1e1b4b', 'theme_footer_text' => '#f5f3ff', 'theme_footer_text_muted' => '#c4b5fd', 'theme_footer_link_hover' => '#a78bfa', 'theme_footer_padding' => '60px', 'theme_footer_link' => '#e9d5ff', 'theme_footer_border' => '#5b21b6',
            'theme_badge_radius' => '10px', 'theme_badge_padding' => '5px 14px', 'theme_badge_font_size' => '12px', 'theme_badge_font_weight' => '500',
            'theme_scrollbar_width' => '8px', 'theme_scrollbar_track' => '#f5f3ff', 'theme_scrollbar_thumb' => '#d8b4fe', 'theme_scrollbar_thumb_hover' => '#c084fc',
            'theme_modal_bg' => '#ffffff', 'theme_modal_radius' => '20px', 'theme_modal_backdrop' => 'rgba(30,27,75,0.6)',
            'theme_table_header_bg' => '#faf8ff', 'theme_table_border' => '#e9d5ff', 'theme_table_hover_bg' => '#f5f3ff'
        ],

        'orange' => [
            'theme_primary' => '#ea580c', 'theme_primary_hover' => '#c2410c', 'theme_primary_dark' => '#9a3412', 'theme_primary_light' => '#fff7ed',
            'theme_secondary' => '#292524', 'theme_secondary_hover' => '#44403c', 'theme_secondary_light' => '#57534e',
            'theme_text_primary' => '#1c1917', 'theme_text_secondary' => '#44403c', 'theme_text_muted' => '#78716c', 'theme_text_light' => '#a8a29e',
            'theme_bg_body' => '#ffffff', 'theme_bg_light' => '#fafaf9', 'theme_bg_gray' => '#fff7ed', 'theme_bg_dark' => '#1c1917',
            'theme_success' => '#16a34a', 'theme_warning' => '#eab308', 'theme_danger' => '#dc2626', 'theme_info' => '#0891b2',
            'theme_border' => '#fed7aa', 'theme_border_light' => '#fff7ed', 'theme_border_dark' => '#fdba74',
            'theme_font_primary' => 'Outfit', 'theme_font_heading' => 'Outfit',
            'theme_font_size_base' => '15px', 'theme_font_size_sm' => '13px', 'theme_font_size_lg' => '18px',
            'theme_radius_xs' => '3px', 'theme_radius_sm' => '5px', 'theme_radius' => '10px', 'theme_radius_lg' => '14px', 'theme_radius_xl' => '18px', 'theme_radius_pill' => '9999px',
            'theme_shadow_sm' => '0 1px 2px rgba(234,88,12,0.06)', 'theme_shadow' => '0 4px 12px rgba(234,88,12,0.1)', 'theme_shadow_lg' => '0 8px 24px rgba(234,88,12,0.15)',
            'theme_btn_padding_x' => '22px', 'theme_btn_padding_y' => '11px', 'theme_btn_font_size' => '14px', 'theme_btn_font_weight' => '600', 'theme_btn_radius' => '8px', 'theme_btn_shadow' => 'none',
            'theme_card_bg' => '#ffffff', 'theme_card_border' => '#fed7aa', 'theme_card_radius' => '14px', 'theme_card_shadow' => '0 2px 8px rgba(234,88,12,0.06)', 'theme_card_hover_shadow' => '0 8px 24px rgba(234,88,12,0.12)', 'theme_card_padding' => '22px',
            'theme_item_title_size' => '14px', 'theme_item_title_weight' => '600', 'theme_item_price_size' => '16px', 'theme_item_hover_scale' => '1.02',
            'theme_input_height' => '46px', 'theme_input_bg' => '#ffffff', 'theme_input_border' => '#fed7aa', 'theme_input_radius' => '8px', 'theme_input_focus_border' => '#ea580c', 'theme_input_focus_shadow' => '0 0 0 3px rgba(234,88,12,0.12)', 'theme_input_placeholder' => '#a8a29e',
            'theme_header_bg' => '#ffffff', 'theme_header_height' => '70px', 'theme_header_shadow' => '0 1px 3px rgba(234,88,12,0.08)',
            'theme_nav_link_color' => '#44403c', 'theme_nav_link_hover' => '#ea580c', 'theme_nav_font_size' => '15px', 'theme_nav_font_weight' => '500',
            'theme_footer_bg' => '#1c1917', 'theme_footer_text' => '#fafaf9', 'theme_footer_text_muted' => '#a8a29e', 'theme_footer_link_hover' => '#fb923c', 'theme_footer_padding' => '55px', 'theme_footer_link' => '#d6d3d1', 'theme_footer_border' => '#44403c',
            'theme_badge_radius' => '6px', 'theme_badge_padding' => '4px 12px', 'theme_badge_font_size' => '12px', 'theme_badge_font_weight' => '600',
            'theme_scrollbar_width' => '8px', 'theme_scrollbar_track' => '#fff7ed', 'theme_scrollbar_thumb' => '#fdba74', 'theme_scrollbar_thumb_hover' => '#fb923c',
            'theme_modal_bg' => '#ffffff', 'theme_modal_radius' => '16px', 'theme_modal_backdrop' => 'rgba(28,25,23,0.6)',
            'theme_table_header_bg' => '#fff7ed', 'theme_table_border' => '#fed7aa', 'theme_table_hover_bg' => '#fffbeb'
        ],

        'gold' => [
            'theme_primary' => '#b8860b', 'theme_primary_hover' => '#996f00', 'theme_primary_dark' => '#7a5800', 'theme_primary_light' => '#fef9e7',
            'theme_secondary' => '#1a1a2e', 'theme_secondary_hover' => '#2d2d44', 'theme_secondary_light' => '#40405a',
            'theme_text_primary' => '#1a1a2e', 'theme_text_secondary' => '#40405a', 'theme_text_muted' => '#6b6b80', 'theme_text_light' => '#9999aa',
            'theme_bg_body' => '#ffffff', 'theme_bg_light' => '#fffef5', 'theme_bg_gray' => '#fef9e7', 'theme_bg_dark' => '#1a1a2e',
            'theme_success' => '#10b981', 'theme_warning' => '#f59e0b', 'theme_danger' => '#ef4444', 'theme_info' => '#06b6d4',
            'theme_border' => '#f5e6b3', 'theme_border_light' => '#fef9e7', 'theme_border_dark' => '#e6d090',
            'theme_font_primary' => 'Cormorant Garamond', 'theme_font_heading' => 'Cormorant Garamond',
            'theme_font_size_base' => '16px', 'theme_font_size_sm' => '14px', 'theme_font_size_lg' => '20px',
            'theme_radius_xs' => '0px', 'theme_radius_sm' => '2px', 'theme_radius' => '4px', 'theme_radius_lg' => '6px', 'theme_radius_xl' => '8px', 'theme_radius_pill' => '9999px',
            'theme_shadow_sm' => '0 1px 3px rgba(184,134,11,0.08)', 'theme_shadow' => '0 4px 16px rgba(184,134,11,0.1)', 'theme_shadow_lg' => '0 8px 32px rgba(184,134,11,0.15)',
            'theme_btn_padding_x' => '28px', 'theme_btn_padding_y' => '14px', 'theme_btn_font_size' => '14px', 'theme_btn_font_weight' => '600', 'theme_btn_radius' => '2px', 'theme_btn_shadow' => 'none',
            'theme_card_bg' => '#ffffff', 'theme_card_border' => '#f5e6b3', 'theme_card_radius' => '4px', 'theme_card_shadow' => '0 2px 8px rgba(184,134,11,0.06)', 'theme_card_hover_shadow' => '0 8px 24px rgba(184,134,11,0.12)', 'theme_card_padding' => '24px',
            'theme_item_title_size' => '15px', 'theme_item_title_weight' => '600', 'theme_item_price_size' => '18px', 'theme_item_hover_scale' => '1.01',
            'theme_input_height' => '50px', 'theme_input_bg' => '#ffffff', 'theme_input_border' => '#f5e6b3', 'theme_input_radius' => '2px', 'theme_input_focus_border' => '#b8860b', 'theme_input_focus_shadow' => '0 0 0 3px rgba(184,134,11,0.1)', 'theme_input_placeholder' => '#9999aa',
            'theme_header_bg' => '#ffffff', 'theme_header_height' => '80px', 'theme_header_shadow' => '0 1px 4px rgba(184,134,11,0.08)',
            'theme_nav_link_color' => '#40405a', 'theme_nav_link_hover' => '#b8860b', 'theme_nav_font_size' => '15px', 'theme_nav_font_weight' => '500',
            'theme_footer_bg' => '#1a1a2e', 'theme_footer_text' => '#f5f5f5', 'theme_footer_text_muted' => '#9999aa', 'theme_footer_link_hover' => '#daa520', 'theme_footer_padding' => '60px', 'theme_footer_link' => '#d4d4d4', 'theme_footer_border' => '#40405a',
            'theme_badge_radius' => '2px', 'theme_badge_padding' => '6px 14px', 'theme_badge_font_size' => '11px', 'theme_badge_font_weight' => '700',
            'theme_scrollbar_width' => '6px', 'theme_scrollbar_track' => '#fef9e7', 'theme_scrollbar_thumb' => '#e6d090', 'theme_scrollbar_thumb_hover' => '#daa520',
            'theme_modal_bg' => '#ffffff', 'theme_modal_radius' => '6px', 'theme_modal_backdrop' => 'rgba(26,26,46,0.7)',
            'theme_table_header_bg' => '#fef9e7', 'theme_table_border' => '#f5e6b3', 'theme_table_hover_bg' => '#fffef5'
        ],
    ];

    public function handle()
    {
        $presetName = $this->argument('preset');

        if (!isset($this->presets[$presetName])) {
            $this->error("Unknown preset: {$presetName}");
            $this->info("Available presets: " . implode(', ', array_keys($this->presets)));
            return Command::FAILURE;
        }

        $this->info("Applying theme preset: {$presetName}");

        // Get current settings
        $gs = Muaadhsetting::findOrFail(1);

        // Apply preset values
        $preset = $this->presets[$presetName];
        foreach ($preset as $key => $value) {
            $gs->{$key} = $value;
        }

        $gs->save();
        cache()->forget('muaadhsettings');

        $this->info("Preset applied to database.");

        // Generate theme-colors.css
        if ($this->option('generate-css') || $this->option('run-audit')) {
            $this->call('theme:generate-css');
        }

        // Run audit if requested
        if ($this->option('run-audit')) {
            $this->info("Running contrast audit...");
            $auditPath = base_path('scripts/contrast-audit');
            $result = shell_exec("cd \"{$auditPath}\" && node audit.js --theme={$presetName} 2>&1");
            $this->line($result);
        }

        $this->info("Theme preset '{$presetName}' applied successfully!");

        return Command::SUCCESS;
    }
}

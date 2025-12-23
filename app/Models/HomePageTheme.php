<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomePageTheme extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'is_active',
        'layout',
        // Section Toggles
        'show_slider',
        'show_hero_search',
        'show_brands',
        'show_categories',
        'show_arrival',
        'show_featured_products',
        'show_deal_of_day',
        'show_top_rated',
        'show_big_save',
        'show_trending',
        'show_best_sellers',
        'show_blogs',
        'show_services',
        'show_newsletter',
        // Section Order
        'order_slider',
        'order_brands',
        'order_categories',
        'order_arrival',
        'order_featured_products',
        'order_deal_of_day',
        'order_top_rated',
        'order_big_save',
        'order_trending',
        'order_best_sellers',
        'order_blogs',
        'order_services',
        'order_newsletter',
        // Section Titles
        'title_brands',
        'title_categories',
        'title_arrival',
        'title_featured_products',
        'title_deal_of_day',
        'title_top_rated',
        'title_big_save',
        'title_trending',
        'title_best_sellers',
        'title_blogs',
        // Product Counts
        'count_featured_products',
        'count_top_rated',
        'count_big_save',
        'count_trending',
        'count_best_sellers',
        'count_blogs',
        // Extra Settings
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_slider' => 'boolean',
        'show_hero_search' => 'boolean',
        'show_brands' => 'boolean',
        'show_categories' => 'boolean',
        'show_arrival' => 'boolean',
        'show_featured_products' => 'boolean',
        'show_deal_of_day' => 'boolean',
        'show_top_rated' => 'boolean',
        'show_big_save' => 'boolean',
        'show_trending' => 'boolean',
        'show_best_sellers' => 'boolean',
        'show_blogs' => 'boolean',
        'show_services' => 'boolean',
        'show_newsletter' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the currently active theme
     */
    public static function getActive()
    {
        return cache()->remember('active_home_theme', 3600, function () {
            return static::where('is_active', true)->first()
                ?? static::first()
                ?? static::createDefault();
        });
    }

    /**
     * Create a default theme if none exists
     */
    public static function createDefault()
    {
        return static::create([
            'name' => 'Default Theme',
            'slug' => 'default',
            'is_active' => true,
            'layout' => 'default',
        ]);
    }

    /**
     * Activate this theme (deactivate others)
     */
    public function activate()
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
        cache()->forget('active_home_theme');
    }

    /**
     * Get sections ordered by their position
     */
    public function getOrderedSections()
    {
        $sections = [
            'slider' => ['show' => $this->show_slider, 'order' => $this->order_slider],
            'brands' => ['show' => $this->show_brands, 'order' => $this->order_brands, 'title' => $this->title_brands],
            'categories' => ['show' => $this->show_categories, 'order' => $this->order_categories, 'title' => $this->title_categories],
            'arrival' => ['show' => $this->show_arrival, 'order' => $this->order_arrival, 'title' => $this->title_arrival],
            'featured_products' => ['show' => $this->show_featured_products, 'order' => $this->order_featured_products, 'title' => $this->title_featured_products, 'count' => $this->count_featured_products],
            'deal_of_day' => ['show' => $this->show_deal_of_day, 'order' => $this->order_deal_of_day, 'title' => $this->title_deal_of_day],
            'top_rated' => ['show' => $this->show_top_rated, 'order' => $this->order_top_rated, 'title' => $this->title_top_rated, 'count' => $this->count_top_rated],
            'big_save' => ['show' => $this->show_big_save, 'order' => $this->order_big_save, 'title' => $this->title_big_save, 'count' => $this->count_big_save],
            'trending' => ['show' => $this->show_trending, 'order' => $this->order_trending, 'title' => $this->title_trending, 'count' => $this->count_trending],
            'best_sellers' => ['show' => $this->show_best_sellers, 'order' => $this->order_best_sellers, 'title' => $this->title_best_sellers, 'count' => $this->count_best_sellers],
            'blogs' => ['show' => $this->show_blogs, 'order' => $this->order_blogs, 'title' => $this->title_blogs, 'count' => $this->count_blogs],
            'services' => ['show' => $this->show_services, 'order' => $this->order_services],
            'newsletter' => ['show' => $this->show_newsletter, 'order' => $this->order_newsletter],
        ];

        // Sort by order and filter only visible sections
        uasort($sections, fn($a, $b) => $a['order'] <=> $b['order']);

        return $sections;
    }
}

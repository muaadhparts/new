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
        'show_hero_carousel',
        'show_hero_search',
        'show_brands',
        'show_categories',
        'show_arrival',
        'show_featured_items',
        'show_deal_of_day',
        'show_top_rated',
        'show_big_save',
        'show_trending',
        'show_best_sellers',
        'show_blogs',
        'show_capabilities',
        'show_newsletter',
        // Section Purchase
        'order_hero_carousel',
        'order_brands',
        'order_categories',
        'order_arrival',
        'order_featured_items',
        'order_deal_of_day',
        'order_top_rated',
        'order_big_save',
        'order_trending',
        'order_best_sellers',
        'order_blogs',
        'order_capabilities',
        'order_newsletter',
        // Section Names
        'name_brands',
        'name_categories',
        'name_arrival',
        'name_featured_items',
        'name_deal_of_day',
        'name_top_rated',
        'name_big_save',
        'name_trending',
        'name_best_sellers',
        'name_blogs',
        // CatalogItem Counts
        'count_featured_items',
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
        'show_hero_carousel' => 'boolean',
        'show_hero_search' => 'boolean',
        'show_brands' => 'boolean',
        'show_categories' => 'boolean',
        'show_arrival' => 'boolean',
        'show_featured_items' => 'boolean',
        'show_deal_of_day' => 'boolean',
        'show_top_rated' => 'boolean',
        'show_big_save' => 'boolean',
        'show_trending' => 'boolean',
        'show_best_sellers' => 'boolean',
        'show_blogs' => 'boolean',
        'show_capabilities' => 'boolean',
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
            'hero_carousel' => ['show' => $this->show_hero_carousel, 'purchase' => $this->order_hero_carousel],
            'brands' => ['show' => $this->show_brands, 'purchase' => $this->order_brands, 'name' => $this->name_brands],
            'categories' => ['show' => $this->show_categories, 'purchase' => $this->order_categories, 'name' => $this->name_categories],
            'arrival' => ['show' => $this->show_arrival, 'purchase' => $this->order_arrival, 'name' => $this->name_arrival],
            'featured_items' => ['show' => $this->show_featured_items, 'purchase' => $this->order_featured_items, 'name' => $this->name_featured_items, 'count' => $this->count_featured_items],
            'deal_of_day' => ['show' => $this->show_deal_of_day, 'purchase' => $this->order_deal_of_day, 'name' => $this->name_deal_of_day],
            'top_rated' => ['show' => $this->show_top_rated, 'purchase' => $this->order_top_rated, 'name' => $this->name_top_rated, 'count' => $this->count_top_rated],
            'big_save' => ['show' => $this->show_big_save, 'purchase' => $this->order_big_save, 'name' => $this->name_big_save, 'count' => $this->count_big_save],
            'trending' => ['show' => $this->show_trending, 'purchase' => $this->order_trending, 'name' => $this->name_trending, 'count' => $this->count_trending],
            'best_sellers' => ['show' => $this->show_best_sellers, 'purchase' => $this->order_best_sellers, 'name' => $this->name_best_sellers, 'count' => $this->count_best_sellers],
            'blogs' => ['show' => $this->show_blogs, 'purchase' => $this->order_blogs, 'name' => $this->name_blogs, 'count' => $this->count_blogs],
            'capabilities' => ['show' => $this->show_capabilities, 'purchase' => $this->order_capabilities],
            'newsletter' => ['show' => $this->show_newsletter, 'purchase' => $this->order_newsletter],
        ];

        // Sort by purchase and filter only visible sections
        uasort($sections, fn($a, $b) => $a['purchase'] <=> $b['purchase']);

        return $sections;
    }
}

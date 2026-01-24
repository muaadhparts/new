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
        'show_hero_search',
        'show_brands',
        'show_categories',
        'show_arrival',
        'show_blogs',
        'show_newsletter',
        // Section Order
        'order_brands',
        'order_categories',
        'order_arrival',
        'order_blogs',
        'order_newsletter',
        // Section Names
        'name_brands',
        'name_categories',
        'name_arrival',
        'name_blogs',
        // Counts
        'count_blogs',
        'count_categories',
        // Extra Settings
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_hero_search' => 'boolean',
        'show_brands' => 'boolean',
        'show_categories' => 'boolean',
        'show_arrival' => 'boolean',
        'show_blogs' => 'boolean',
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
            'brands' => ['show' => $this->show_brands, 'order' => $this->order_brands, 'name' => $this->name_brands],
            'categories' => ['show' => $this->show_categories, 'order' => $this->order_categories, 'name' => $this->name_categories],
            'arrival' => ['show' => $this->show_arrival, 'order' => $this->order_arrival, 'name' => $this->name_arrival],
            'blogs' => ['show' => $this->show_blogs, 'order' => $this->order_blogs, 'name' => $this->name_blogs, 'count' => $this->count_blogs],
            'newsletter' => ['show' => $this->show_newsletter, 'order' => $this->order_newsletter],
        ];

        // Sort by order and filter only visible sections
        uasort($sections, fn($a, $b) => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $sections;
    }
}

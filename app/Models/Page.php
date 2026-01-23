<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * ============================================================================
 * PAGE MODEL
 * ============================================================================
 *
 * Policy pages only: terms, privacy, refund.
 * Replaces: static_content (for policy pages only)
 *
 * IMPORTANT: This is NOT a CMS. Do not add features like:
 * - Dynamic page creation
 * - User permissions
 * - Categories/tags
 * - Comments
 * - Media galleries
 *
 * Usage:
 * ------
 * Page::findBySlug('terms');
 * Page::getTerms();
 * Page::getPrivacy();
 * Page::getRefund();
 *
 * ============================================================================
 */
class Page extends Model
{
    protected $table = 'pages';

    protected $fillable = [
        'slug',
        'title',
        'title_ar',
        'content',
        'content_ar',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Allowed slugs - ONLY policy pages
     */
    const ALLOWED_SLUGS = ['terms', 'privacy', 'refund'];

    /**
     * Cache settings
     */
    const CACHE_PREFIX = 'pages';
    const CACHE_TTL = 3600;

    /**
     * Find page by slug
     *
     * @param string $slug
     * @return static|null
     */
    public static function findBySlug(string $slug): ?self
    {
        if (!in_array($slug, self::ALLOWED_SLUGS)) {
            return null;
        }

        $cacheKey = self::CACHE_PREFIX . ":{$slug}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($slug) {
            return static::where('slug', $slug)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get Terms & Conditions page
     */
    public static function getTerms(): ?self
    {
        return self::findBySlug('terms');
    }

    /**
     * Get Privacy Policy page
     */
    public static function getPrivacy(): ?self
    {
        return self::findBySlug('privacy');
    }

    /**
     * Get Refund Policy page
     */
    public static function getRefund(): ?self
    {
        return self::findBySlug('refund');
    }

    /**
     * Get content based on locale
     *
     * @param string|null $locale
     * @return string|null
     */
    public function getContent(?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ar' && !empty($this->content_ar)) {
            return $this->content_ar;
        }

        return $this->content;
    }

    /**
     * Get title based on locale
     *
     * @param string|null $locale
     * @return string
     */
    public function getTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ar' && !empty($this->title_ar)) {
            return $this->title_ar;
        }

        return $this->title;
    }

    /**
     * Clear page cache
     */
    public static function clearCache(?string $slug = null): void
    {
        if ($slug) {
            Cache::forget(self::CACHE_PREFIX . ":{$slug}");
        } else {
            foreach (self::ALLOWED_SLUGS as $s) {
                Cache::forget(self::CACHE_PREFIX . ":{$s}");
            }
        }
    }

    /**
     * Boot method - clear cache on save
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($page) {
            self::clearCache($page->slug);
        });

        static::deleted(function ($page) {
            self::clearCache($page->slug);
        });
    }
}

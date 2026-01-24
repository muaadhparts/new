<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Page Model
 *
 * Policy pages only: terms, privacy, refund.
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

    const ALLOWED_SLUGS = ['terms', 'privacy', 'refund'];
    const CACHE_PREFIX = 'pages';
    const CACHE_TTL = 3600;

    /**
     * Find page by slug
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

    public static function getTerms(): ?self
    {
        return self::findBySlug('terms');
    }

    public static function getPrivacy(): ?self
    {
        return self::findBySlug('privacy');
    }

    public static function getRefund(): ?self
    {
        return self::findBySlug('refund');
    }

    /**
     * Get content based on locale
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
     */
    public function getTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        if ($locale === 'ar' && !empty($this->title_ar)) {
            return $this->title_ar;
        }

        return $this->title;
    }

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

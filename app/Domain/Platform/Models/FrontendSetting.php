<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * FrontendSetting Model
 *
 * Frontend configuration for contact and page visibility.
 */
class FrontendSetting extends Model
{
    protected $table = 'frontend_settings';

    protected $fillable = [
        'contact_email',
        'street',
        'phone',
        'fax',
        'email',
        'home',
        'blog',
        'faq',
        'contact',
        'category',
        'newsletter',
    ];

    public $timestamps = false;

    protected $casts = [
        'home' => 'boolean',
        'blog' => 'boolean',
        'faq' => 'boolean',
        'contact' => 'boolean',
        'category' => 'boolean',
        'newsletter' => 'boolean',
    ];

    /**
     * Get the frontend settings (singleton)
     */
    public static function getInstance(): ?self
    {
        return cache()->remember('frontend_settings', 3600, function () {
            return static::first();
        });
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        cache()->forget('frontend_settings');
    }
}

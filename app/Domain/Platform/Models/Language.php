<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Language Model
 *
 * Manages language configurations for the platform.
 * Supports Arabic (RTL) and English.
 */
class Language extends Model
{
    public $timestamps = false;

    protected $fillable = ['language', 'file', 'is_default', 'rtl', 'name'];

    protected $casts = [
        'is_default' => 'boolean',
        'rtl' => 'boolean',
    ];

    /**
     * Check if this language uses RTL direction.
     */
    public function isRtl(): bool
    {
        return $this->rtl || $this->file === 'ar';
    }

    /**
     * Get the default language.
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get the language file path.
     */
    public function getFilePath(): string
    {
        return resource_path("lang/{$this->file}.json");
    }
}

<?php
/**
 * MUAADH EPC - Multi-Merchant Auto Parts Catalog
 *
 * @package    MUAADH\Models
 * @author     MUAADH Development Team
 * @copyright  2024-2026 MUAADH EPC
 * @license    Proprietary
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Language Model
 *
 * Manages language configurations for the MUAADH EPC platform.
 * Supports Arabic (RTL) and English for auto parts catalog localization.
 *
 * @property int $id
 * @property string $language Language name
 * @property string $file Language file identifier
 * @property bool $is_default Whether this is the default language
 */
class Language extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['language', 'file', 'is_default', 'rtl'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
        'rtl' => 'boolean',
    ];

    /**
     * Check if this language uses RTL direction.
     *
     * @return bool
     */
    public function isRtl(): bool
    {
        return $this->rtl || $this->file === 'ar';
    }

    /**
     * Get the default language.
     *
     * @return self|null
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get the language file path.
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return resource_path("lang/{$this->file}.json");
    }
}

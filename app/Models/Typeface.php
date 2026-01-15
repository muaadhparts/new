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
 * Typeface Model
 *
 * Manages typography settings for the MUAADH EPC platform.
 * Supports both Latin and Arabic typefaces for bilingual auto parts catalog.
 *
 * @property int $id
 * @property bool $is_default Whether this is the default typeface
 * @property string $font_family CSS font-family name
 * @property string $font_value Google Fonts or custom font value
 */
class Typeface extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['is_default', 'font_family', 'font_value'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'typefaces';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Recommended typefaces for Arabic content.
     */
    public const ARABIC_TYPEFACES = [
        'Cairo',
        'Tajawal',
        'Almarai',
        'Amiri',
        'Noto Naskh Arabic',
    ];

    /**
     * Recommended typefaces for English content.
     */
    public const ENGLISH_TYPEFACES = [
        'Poppins',
        'Roboto',
        'Open Sans',
        'Inter',
        'Montserrat',
    ];

    /**
     * Get the default typeface.
     *
     * @return self|null
     */
    public static function getDefault(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get the CSS @import URL for Google Fonts.
     *
     * @return string|null
     */
    public function getGoogleFontUrl(): ?string
    {
        if (empty($this->font_value)) {
            return null;
        }

        $fontName = urlencode($this->font_family);
        return "https://fonts.googleapis.com/css2?family={$fontName}:wght@400;500;600;700&display=swap";
    }

    /**
     * Get the CSS font-family declaration.
     *
     * @return string
     */
    public function getCssFontFamily(): string
    {
        return "'{$this->font_family}', sans-serif";
    }

    /**
     * Check if this is an Arabic-optimized typeface.
     *
     * @return bool
     */
    public function isArabicTypeface(): bool
    {
        return in_array($this->font_family, self::ARABIC_TYPEFACES);
    }
}

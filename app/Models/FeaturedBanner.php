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
 * FeaturedBanner Model
 *
 * Manages promotional banners on the MUAADH EPC homepage.
 * Supports promotional campaigns for auto parts categories or merchant highlights.
 *
 * @property int $id
 * @property string $link Banner click destination URL
 * @property string $photo Banner image filename
 */
class FeaturedBanner extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['link', 'photo', 'title', 'position', 'status'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Banner position constants.
     */
    public const POSITION_TOP = 'top';
    public const POSITION_MIDDLE = 'middle';
    public const POSITION_BOTTOM = 'bottom';
    public const POSITION_SIDEBAR = 'sidebar';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Get the full photo URL.
     *
     * @return string
     */
    public function getPhotoUrl(): string
    {
        if (empty($this->photo)) {
            return asset('assets/images/noimage.png');
        }
        return asset('assets/images/' . $this->photo);
    }

    /**
     * Check if banner has a valid link.
     *
     * @return bool
     */
    public function hasLink(): bool
    {
        return !empty($this->link) && filter_var($this->link, FILTER_VALIDATE_URL);
    }

    /**
     * Get active banners ordered by position.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive()
    {
        return static::where('status', true)
            ->orderBy('position', 'asc')
            ->get();
    }
}

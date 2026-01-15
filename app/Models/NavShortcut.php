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
 * NavShortcut Model
 *
 * Manages navigation shortcut links on the MUAADH EPC platform.
 * Used for highlighting popular auto parts categories, brands, or promotions.
 *
 * @property int $id
 * @property string $name Link display name
 * @property string $link Destination URL
 * @property string $photo Icon/thumbnail image
 */
class NavShortcut extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'nav_shortcuts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['name', 'link', 'photo', 'position', 'status'];

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
     * Get the translated name based on locale.
     *
     * @return string
     */
    public function getLocalizedName(): string
    {
        return __($this->name);
    }

    /**
     * Check if this link is external.
     *
     * @return bool
     */
    public function isExternal(): bool
    {
        if (empty($this->link)) {
            return false;
        }
        return !str_contains($this->link, config('app.url'));
    }

    /**
     * Get active nav shortcuts ordered by position.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActive()
    {
        return static::where('status', true)
            ->orderBy('position', 'asc')
            ->get();
    }

    /**
     * Get the link target attribute.
     *
     * @return string
     */
    public function getLinkTarget(): string
    {
        return $this->isExternal() ? '_blank' : '_self';
    }
}

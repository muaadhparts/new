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
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * NetworkPresence Model
 *
 * Manages network presence links for merchants on the MUAADH EPC platform.
 * Allows merchants to display their social media presence on their store pages.
 *
 * @property int $id
 * @property int $user_id Merchant user ID
 * @property string $link Social media profile URL
 * @property string $icon Font Awesome icon class
 */
class NetworkPresence extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'network_presences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['user_id', 'link', 'icon'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * Common social platform icons.
     */
    public const ICON_FACEBOOK = 'fab fa-facebook-f';
    public const ICON_TWITTER = 'fab fa-twitter';
    public const ICON_INSTAGRAM = 'fab fa-instagram';
    public const ICON_WHATSAPP = 'fab fa-whatsapp';
    public const ICON_LINKEDIN = 'fab fa-linkedin-in';

    /**
     * Get the merchant that owns this network presence.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Alias for user() - get merchant.
     *
     * @return BelongsTo
     */
    public function merchant(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get network presences for a specific merchant.
     *
     * @param int $merchantId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function forMerchant(int $merchantId)
    {
        return static::where('user_id', $merchantId)->get();
    }

    /**
     * Get the platform name from the icon class.
     *
     * @return string
     */
    public function getPlatformName(): string
    {
        $iconToName = [
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
            'linkedin' => 'LinkedIn',
        ];

        foreach ($iconToName as $key => $name) {
            if (str_contains($this->icon, $key)) {
                return $name;
            }
        }

        return 'Social';
    }
}

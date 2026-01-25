<?php

namespace App\Domain\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Network Presence Model
 *
 * Social media links for merchants.
 */
class NetworkPresence extends Model
{
    protected $table = 'network_presences';

    protected $fillable = [
        'user_id',
        'link',
        'icon',
    ];

    public $timestamps = false;

    // Common social platform icons
    public const ICON_FACEBOOK = 'fab fa-facebook-f';
    public const ICON_TWITTER = 'fab fa-twitter';
    public const ICON_INSTAGRAM = 'fab fa-instagram';
    public const ICON_WHATSAPP = 'fab fa-whatsapp';
    public const ICON_LINKEDIN = 'fab fa-linkedin-in';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function merchant(): BelongsTo
    {
        return $this->user();
    }

    public static function forMerchant(int $merchantId)
    {
        return static::where('user_id', $merchantId)->get();
    }

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

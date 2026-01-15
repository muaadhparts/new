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
 * ConnectConfig Model
 *
 * Manages social OAuth settings and platform social links for MUAADH EPC.
 * Supports Facebook and Google OAuth for customer authentication.
 *
 * @property int $id
 * @property string $facebook Facebook page URL
 * @property string $twitter Twitter profile URL
 * @property string $gplus Google Plus (deprecated)
 * @property string $linkedin LinkedIn profile URL
 * @property string $dribble Dribbble profile URL
 * @property bool $f_status Facebook status
 * @property bool $t_status Twitter status
 * @property bool $g_status Google status
 * @property bool $l_status LinkedIn status
 * @property bool $d_status Dribbble status
 * @property bool $f_check Facebook OAuth enabled
 * @property bool $g_check Google OAuth enabled
 * @property string $fclient_id Facebook OAuth Client ID
 * @property string $fclient_secret Facebook OAuth Client Secret
 * @property string $fredirect Facebook OAuth Redirect URL
 * @property string $gclient_id Google OAuth Client ID
 * @property string $gclient_secret Google OAuth Client Secret
 * @property string $gredirect Google OAuth Redirect URL
 */
class ConnectConfig extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'connect_configs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'facebook', 'twitter', 'gplus', 'linkedin', 'dribble',
        'f_status', 't_status', 'g_status', 'l_status', 'd_status',
        'f_check', 'g_check',
        'fclient_id', 'fclient_secret', 'fredirect',
        'gclient_id', 'gclient_secret', 'gredirect'
    ];

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
        'f_status' => 'boolean',
        't_status' => 'boolean',
        'g_status' => 'boolean',
        'l_status' => 'boolean',
        'd_status' => 'boolean',
        'f_check' => 'boolean',
        'g_check' => 'boolean',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<string>
     */
    protected $hidden = [
        'fclient_secret',
        'gclient_secret',
    ];

    /**
     * Check if Facebook OAuth is enabled.
     *
     * @return bool
     */
    public function isFacebookOAuthEnabled(): bool
    {
        return $this->f_check && !empty($this->fclient_id);
    }

    /**
     * Check if Google OAuth is enabled.
     *
     * @return bool
     */
    public function isGoogleOAuthEnabled(): bool
    {
        return $this->g_check && !empty($this->gclient_id);
    }

    /**
     * Get active social links array.
     *
     * @return array<string, string>
     */
    public function getActiveSocialLinks(): array
    {
        $links = [];

        if ($this->f_status && !empty($this->facebook)) {
            $links['facebook'] = $this->facebook;
        }
        if ($this->t_status && !empty($this->twitter)) {
            $links['twitter'] = $this->twitter;
        }
        if ($this->l_status && !empty($this->linkedin)) {
            $links['linkedin'] = $this->linkedin;
        }

        return $links;
    }
}

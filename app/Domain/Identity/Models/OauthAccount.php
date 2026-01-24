<?php

namespace App\Domain\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * OauthAccount Model - Social login accounts
 *
 * Domain: Identity
 * Table: oauth_accounts
 *
 * @property int $id
 * @property int $user_id
 * @property string $provider
 * @property string $provider_id
 */
class OauthAccount extends Model
{
    protected $table = 'oauth_accounts';

    protected $fillable = ['provider_id', 'provider'];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Get the user that owns this OAuth account
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}

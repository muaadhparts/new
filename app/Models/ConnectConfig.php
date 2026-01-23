<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @deprecated Use PlatformSetting::getGroup('social_login') instead.
 *
 * This model exists for backward compatibility only.
 * New code should use:
 *   PlatformSetting::get('social_login', 'google_client_id')
 *   PlatformSetting::get('social_login', 'facebook_client_id')
 */
class ConnectConfig extends Model
{
    protected $table = 'connect_configs';

    protected $fillable = [
        'fclient_id', 'fclient_secret', 'gclient_id', 'gclient_secret',
        'f_check', 'g_check'
    ];
}

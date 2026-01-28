<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Mailing List Model
 *
 * Newsletter subscribers.
 */
class MailingList extends Model
{
    protected $table = 'subscribers';

    protected $fillable = ['email'];

    public $timestamps = false;
}

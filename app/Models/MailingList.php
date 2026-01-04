<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailingList extends Model
{
    protected $table = 'mailing_list';

    protected $fillable = ['email'];

    public $timestamps = false;
}

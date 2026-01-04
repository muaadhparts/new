<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatThread extends Model
{
    protected $table = 'chat_threads';

    public function sent()
    {
        return $this->belongsTo('App\Models\User', 'sent_user');
    }

    public function recieved()
    {
        return $this->belongsTo('App\Models\User', 'recieved_user');
    }

    public function chatEntries()
    {
        return $this->hasMany('App\Models\ChatEntry');
    }
}

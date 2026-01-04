<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatEntry extends Model
{
    protected $table = 'chat_entries';

    protected $fillable = ['chat_thread_id', 'message', 'sent_user', 'recieved_user'];

    public function chatThread()
    {
        return $this->belongsTo('App\Models\ChatThread', 'chat_thread_id');
    }
}

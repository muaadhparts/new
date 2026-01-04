<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NoteResponse extends Model
{
    protected $table = 'note_responses';

    protected $fillable = ['buyer_note_id', 'user_id', 'text'];

    public function user()
    {
        return $this->belongsTo('App\Models\User')->withDefault();
    }

    public function buyerNote()
    {
        return $this->belongsTo('App\Models\BuyerNote', 'buyer_note_id')->withDefault();
    }

    public function subResponses()
    {
        return $this->hasMany('App\Models\SubReply');
    }
}

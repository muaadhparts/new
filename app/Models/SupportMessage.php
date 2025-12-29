<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Support Message Model
 *
 * Messages within a support thread.
 */
class SupportMessage extends Model
{
    protected $table = 'support_messages';

    protected $fillable = [
        'thread_id',
        'message',
        'user_id',
    ];

    /**
     * Get the thread that owns the message.
     */
    public function thread()
    {
        return $this->belongsTo(SupportThread::class, 'thread_id')->withDefault();
    }

    /**
     * Alias for backward compatibility.
     * @deprecated Use thread() instead
     */
    public function conversation()
    {
        return $this->thread();
    }

    /**
     * Get the user that sent the message.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}

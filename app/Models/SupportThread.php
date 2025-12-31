<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Support Thread Model
 *
 * Handles support tickets and disputes between users and admin.
 */
class SupportThread extends Model
{
    protected $table = 'support_threads';

    protected $fillable = [
        'subject',
        'user_id',
        'message',
        'type',
        'purchase_number',
    ];

    /**
     * Get the user that owns the thread.
     */
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    /**
     * Get the admin associated with the thread.
     */
    public function admin()
    {
        return $this->belongsTo(Admin::class)->withDefault();
    }

    /**
     * Get all messages for this thread.
     */
    public function messages()
    {
        return $this->hasMany(SupportMessage::class, 'thread_id');
    }

    /**
     * Get notifications for this thread.
     */
    public function notifications()
    {
        return $this->hasMany(UserCatalogEvent::class, 'conversation1_id');
    }

    /**
     * Scope for tickets only.
     */
    public function scopeTickets($query)
    {
        return $query->where('type', 'Ticket');
    }

    /**
     * Scope for disputes only.
     */
    public function scopeDisputes($query)
    {
        return $query->where('type', 'Dispute');
    }
}

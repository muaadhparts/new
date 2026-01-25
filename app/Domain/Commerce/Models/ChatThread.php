<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Chat Thread Model
 *
 * Represents a chat conversation between users.
 */
class ChatThread extends Model
{
    protected $table = 'chat_threads';

    protected $fillable = [
        'sent_user',
        'recieved_user',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function sent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_user');
    }

    public function recieved(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recieved_user');
    }

    public function chatEntries(): HasMany
    {
        return $this->hasMany(ChatEntry::class, 'chat_thread_id');
    }
}

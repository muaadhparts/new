<?php

namespace App\Domain\Commerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Chat Entry Model
 *
 * Represents a single message in a chat thread.
 */
class ChatEntry extends Model
{
    protected $table = 'chat_entries';

    protected $fillable = [
        'chat_thread_id',
        'message',
        'sent_user',
        'recieved_user',
    ];

    // ========================================
    // Relationships
    // ========================================

    public function chatThread(): BelongsTo
    {
        return $this->belongsTo(ChatThread::class, 'chat_thread_id');
    }
}

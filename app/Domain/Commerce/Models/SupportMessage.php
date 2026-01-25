<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    // ========================================
    // Relationships
    // ========================================

    public function thread(): BelongsTo
    {
        return $this->belongsTo(SupportThread::class, 'thread_id')->withDefault();
    }

    /**
     * Alias for backward compatibility.
     * @deprecated Use thread() instead
     */
    public function conversation(): BelongsTo
    {
        return $this->thread();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }
}

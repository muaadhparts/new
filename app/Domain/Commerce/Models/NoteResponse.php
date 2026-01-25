<?php

namespace App\Domain\Commerce\Models;

use App\Domain\Identity\Models\User;
use App\Domain\Commerce\Models\BuyerNote;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * NoteResponse Model
 *
 * Domain: Commerce
 * Table: note_responses
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $buyer_note_id
 * @property string $text
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class NoteResponse extends Model
{
    protected $table = 'note_responses';

    protected $fillable = ['buyer_note_id', 'user_id', 'text'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault();
    }

    public function buyerNote(): BelongsTo
    {
        return $this->belongsTo(BuyerNote::class, 'buyer_note_id')->withDefault();
    }

    public function subResponses(): HasMany
    {
        return $this->hasMany(SubReply::class);
    }
}

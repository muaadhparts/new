<?php

namespace App\Domain\Merchant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Identity\Models\User;

/**
 * MerchantStockUpdate Model - Bulk stock update records
 *
 * Domain: Merchant
 * Table: merchant_stock_updates
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $file_name
 * @property string|null $file_path
 * @property string|null $update_type
 * @property string $status
 * @property int|null $total_rows
 * @property int|null $updated_rows
 * @property int|null $failed_rows
 * @property string|null $error_log
 */
class MerchantStockUpdate extends Model
{
    use HasFactory;

    protected $table = 'merchant_stock_updates';

    protected $fillable = [
        'user_id',
        'file_name',
        'file_path',
        'update_type',
        'status',
        'total_rows',
        'updated_rows',
        'failed_rows',
        'error_log',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // =========================================================
    // STATUS METHODS
    // =========================================================

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(int $updatedRows, int $failedRows = 0, ?string $errorLog = null): void
    {
        $this->update([
            'status' => 'completed',
            'updated_rows' => $updatedRows,
            'failed_rows' => $failedRows,
            'error_log' => $errorLog,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed(string $errorLog): void
    {
        $this->update([
            'status' => 'failed',
            'error_log' => $errorLog,
            'completed_at' => now(),
        ]);
    }
}

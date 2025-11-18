<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorStockUpdate extends Model
{
    use HasFactory;

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

    /**
     * Get the vendor/user who initiated this update
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if update is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if update is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if update is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if update failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark update as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark update as completed
     */
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

    /**
     * Mark update as failed
     */
    public function markAsFailed(string $errorLog): void
    {
        $this->update([
            'status' => 'failed',
            'error_log' => $errorLog,
            'completed_at' => now(),
        ]);
    }
}

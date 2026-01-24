<?php

namespace App\Domain\Identity\Observers;

use App\Domain\Identity\Models\Operator;
use Illuminate\Support\Facades\Hash;

/**
 * Operator Observer
 *
 * Handles Operator model lifecycle events.
 */
class OperatorObserver
{
    /**
     * Handle the Operator "creating" event.
     */
    public function creating(Operator $operator): void
    {
        // Set default status
        if (!isset($operator->status)) {
            $operator->status = 1;
        }

        // Hash password if provided as plain text
        if (!empty($operator->password) && !$this->isHashed($operator->password)) {
            $operator->password = Hash::make($operator->password);
        }
    }

    /**
     * Handle the Operator "updating" event.
     */
    public function updating(Operator $operator): void
    {
        // Hash password if changed and not already hashed
        if ($operator->isDirty('password') && !$this->isHashed($operator->password)) {
            $operator->password = Hash::make($operator->password);
        }

        // Track login
        if ($operator->isDirty('last_login_at')) {
            $operator->login_count = ($operator->login_count ?? 0) + 1;
        }
    }

    /**
     * Handle the Operator "deleted" event.
     */
    public function deleted(Operator $operator): void
    {
        // Log operator deletion for audit
        \Log::info('Operator deleted', [
            'operator_id' => $operator->id,
            'email' => $operator->email,
            'deleted_by' => auth('operator')->id(),
        ]);
    }

    /**
     * Check if password is already hashed
     */
    protected function isHashed(string $password): bool
    {
        return strlen($password) === 60 && preg_match('/^\$2[ayb]\$.{56}$/', $password);
    }
}

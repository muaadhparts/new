<?php

namespace App\Domain\Platform\Traits;

/**
 * Has Status Trait
 *
 * Provides status management functionality for models.
 */
trait HasStatus
{
    /**
     * Boot the trait
     */
    public static function bootHasStatus(): void
    {
        static::creating(function ($model) {
            if (!isset($model->{$model->getStatusColumn()})) {
                $model->{$model->getStatusColumn()} = $model->getDefaultStatus();
            }
        });
    }

    /**
     * Get the status column name
     */
    public function getStatusColumn(): string
    {
        return $this->statusColumn ?? 'status';
    }

    /**
     * Get the default status
     */
    public function getDefaultStatus(): mixed
    {
        return $this->defaultStatus ?? 1;
    }

    /**
     * Get active status value
     */
    public function getActiveStatus(): mixed
    {
        return $this->activeStatus ?? 1;
    }

    /**
     * Get inactive status value
     */
    public function getInactiveStatus(): mixed
    {
        return $this->inactiveStatus ?? 0;
    }

    /**
     * Check if model is active
     */
    public function isActive(): bool
    {
        return $this->{$this->getStatusColumn()} == $this->getActiveStatus();
    }

    /**
     * Check if model is inactive
     */
    public function isInactive(): bool
    {
        return !$this->isActive();
    }

    /**
     * Activate the model
     */
    public function activate(): bool
    {
        return $this->update([$this->getStatusColumn() => $this->getActiveStatus()]);
    }

    /**
     * Deactivate the model
     */
    public function deactivate(): bool
    {
        return $this->update([$this->getStatusColumn() => $this->getInactiveStatus()]);
    }

    /**
     * Toggle the status
     */
    public function toggleStatus(): bool
    {
        return $this->isActive() ? $this->deactivate() : $this->activate();
    }

    /**
     * Scope to active records
     */
    public function scopeActive($query)
    {
        return $query->where($this->getStatusColumn(), $this->getActiveStatus());
    }

    /**
     * Scope to inactive records
     */
    public function scopeInactive($query)
    {
        return $query->where($this->getStatusColumn(), $this->getInactiveStatus());
    }

    /**
     * Scope by status
     */
    public function scopeStatus($query, $status)
    {
        return $query->where($this->getStatusColumn(), $status);
    }
}

<?php

namespace App\Domain\Platform\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Module Model
 *
 * Represents installed modules/plugins in the platform.
 *
 * @property int $id
 * @property string $name
 * @property string|null $keyword
 * @property string|null $type
 * @property string|null $uninstall_files
 * @property \Carbon\Carbon|null $created_at
 * @property \Carbon\Carbon|null $updated_at
 */
class Module extends Model
{
    protected $table = 'modules';

    protected $fillable = [
        'name',
        'keyword',
        'type',
        'uninstall_files',
    ];

    protected $casts = [
        'uninstall_files' => 'array',
    ];

    // ========================================
    // Scopes
    // ========================================

    /**
     * Scope to filter by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to find by keyword
     */
    public function scopeByKeyword($query, string $keyword)
    {
        return $query->where('keyword', $keyword);
    }

    // ========================================
    // Helper Methods
    // ========================================

    /**
     * Check if module is installed
     */
    public static function isInstalled(string $keyword): bool
    {
        return static::where('keyword', $keyword)->exists();
    }

    /**
     * Get uninstall files as array
     */
    public function getUninstallFilesArray(): array
    {
        if (is_array($this->uninstall_files)) {
            return $this->uninstall_files;
        }

        if (is_string($this->uninstall_files)) {
            return json_decode($this->uninstall_files, true) ?? [];
        }

        return [];
    }
}

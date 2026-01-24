<?php

namespace App\Domain\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * OperatorRole Model - Roles and permissions for operators
 *
 * Domain: Identity
 * Table: operator_roles
 *
 * @property int $id
 * @property string $name
 * @property string|null $section
 */
class OperatorRole extends Model
{
    protected $table = 'operator_roles';

    protected $fillable = ['name', 'section'];

    public $timestamps = false;

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Get all operators with this role
     */
    public function operators(): HasMany
    {
        return $this->hasMany(Operator::class, 'role_id');
    }

    // =========================================================
    // AUTHORIZATION
    // =========================================================

    /**
     * Check if this role has access to a specific section
     */
    public function sectionCheck($value): bool
    {
        $sections = explode(" , ", $this->section);
        return in_array($value, $sections);
    }
}

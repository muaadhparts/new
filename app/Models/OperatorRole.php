<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Operator Role Model
 *
 * Manages operator user roles and permissions.
 */
class OperatorRole extends Model
{
    protected $table = 'operator_roles';

    protected $fillable = ['name', 'section'];

    public $timestamps = false;

    /**
     * Get all operators with this role.
     */
    public function operators()
    {
        return $this->hasMany(Operator::class, 'role_id');
    }

    /**
     * Check if this role has access to a specific section.
     */
    public function sectionCheck($value)
    {
        $sections = explode(" , ", $this->section);
        return in_array($value, $sections);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Admin Role Model
 *
 * Manages admin user roles and permissions.
 */
class AdminRole extends Model
{
    protected $table = 'admin_roles';

    protected $fillable = ['name', 'section'];

    public $timestamps = false;

    /**
     * Get all admins with this role.
     */
    public function admins()
    {
        return $this->hasMany(Admin::class, 'role_id');
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

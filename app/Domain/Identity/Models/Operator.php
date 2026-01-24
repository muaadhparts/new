<?php

namespace App\Domain\Identity\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Operator Model - Admin/Staff users
 *
 * Domain: Identity
 * Table: operators
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string|null $phone
 * @property int|null $role_id
 * @property string|null $photo
 */
class Operator extends Authenticatable
{
    protected $table = 'operators';

    protected $guard = 'operator';

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'email_token',
        'role_id', 'photo', 'created_at', 'updated_at', 'remember_token', 'shop_name'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // =========================================================
    // RELATIONS
    // =========================================================

    /**
     * Get the role for this operator
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(OperatorRole::class, 'role_id')->withDefault();
    }

    // =========================================================
    // AUTHORIZATION
    // =========================================================

    /**
     * Check if operator is super admin
     */
    public function IsSuper(): bool
    {
        return $this->id == 1;
    }

    /**
     * Check if operator has access to a specific section
     */
    public function sectionCheck($value): bool
    {
        $sections = explode(" , ", $this->role->section);
        return in_array($value, $sections);
    }
}

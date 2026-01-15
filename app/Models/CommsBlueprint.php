<?php
/**
 * MUAADH EPC - Multi-Merchant Auto Parts Catalog
 *
 * @package    MUAADH\Models
 * @author     MUAADH Development Team
 * @copyright  2024-2026 MUAADH EPC
 * @license    Proprietary
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * CommsBlueprint Model
 *
 * Manages communication templates for the MUAADH EPC platform notifications.
 * Used for purchase confirmations, shipping updates, merchant communications, etc.
 *
 * @property int $id
 * @property string $email_type Template identifier (e.g., 'purchase_confirm', 'shipping_update')
 * @property string $email_subject Email subject line
 * @property string $email_body Email body content with placeholders
 * @property bool $status Active/Inactive status
 */
class CommsBlueprint extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'comms_blueprints';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = ['email_type', 'email_subject', 'email_body', 'status'];

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'boolean',
    ];

    /**
     * Communication template type constants for MUAADH EPC.
     */
    public const TYPE_PURCHASE_CONFIRM = 'purchase_confirm';
    public const TYPE_SHIPPING_UPDATE = 'shipping_update';
    public const TYPE_MERCHANT_WELCOME = 'merchant_welcome';
    public const TYPE_PASSWORD_RESET = 'password_reset';
    public const TYPE_VERIFICATION = 'verification';

    /**
     * Get template by type.
     *
     * @param string $type
     * @return self|null
     */
    public static function getByType(string $type): ?self
    {
        return static::where('email_type', $type)
            ->where('status', true)
            ->first();
    }

    /**
     * Parse template body with given data.
     *
     * @param array<string, mixed> $data
     * @return string
     */
    public function parseBody(array $data): string
    {
        $body = $this->email_body;
        foreach ($data as $key => $value) {
            $body = str_replace("{{" . $key . "}}", $value, $body);
        }
        return $body;
    }

    /**
     * Check if template is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === true;
    }
}

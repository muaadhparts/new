<?php

namespace App\Domain\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Log;

class NissanCredential extends Model
{
    protected $table = 'nissan_credentials';

    protected $fillable = ['email', 'password', 'cookie'];

    public $timestamps = true;

    /**
     * Check if value is Laravel encrypted payload.
     */
    protected function isEncryptedPayload(?string $value): bool
    {
        if (!is_string($value) || $value === '') return false;

        $decoded = json_decode(base64_decode($value, true) ?: '', true);

        return is_array($decoded)
            && isset($decoded['iv'], $decoded['value'])
            && (isset($decoded['mac']) || isset($decoded['tag']));
    }

    /**
     * Auto-encrypt on write, avoiding double encryption.
     */
    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;
            return;
        }

        if ($this->isEncryptedPayload($value)) {
            $this->attributes['password'] = $value;
            return;
        }

        $this->attributes['password'] = Crypt::encryptString((string) $value);
    }

    /**
     * Auto-decrypt on read if Laravel payload format.
     */
    public function getPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!$this->isEncryptedPayload($value)) {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::warning('Failed to decrypt NissanCredential.password: '.$e->getMessage());
            return $value;
        }
    }
}

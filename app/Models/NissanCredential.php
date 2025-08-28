<?php

namespace App\Models;

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
     * تحقّق إن كانت القيمة Payload مشفّرة بطريقة Laravel.
     */
    protected function isEncryptedPayload(?string $value): bool
    {
        if (!is_string($value) || $value === '') return false;

        $decoded = json_decode(base64_decode($value, true) ?: '', true);

        // dd(['looks_encrypted' => is_array($decoded), 'keys' => array_keys((array)$decoded)]); // debug

        return is_array($decoded)
            && isset($decoded['iv'], $decoded['value'])
            && (isset($decoded['mac']) || isset($decoded['tag']));
    }

    /**
     * تشفير تلقائي عند الكتابة، مع تجنّب التشفير المزدوج.
     */
    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            $this->attributes['password'] = $value;
            return;
        }

        // لو وصلتنا قيمة مشفّرة مسبقًا، خزّنها كما هي.
        if ($this->isEncryptedPayload($value)) {
            $this->attributes['password'] = $value;
            return;
        }

        $this->attributes['password'] = Crypt::encryptString((string) $value);
        // dd('// encrypted password set'); // debug
    }

    /**
     * فك التشفير عند القراءة فقط إن كانت الصيغة Payload لارافيل.
     * عند الفشل نسجل تحذيرًا ونُعيد القيمة كما هي لتستطيع إصلاحها سريعًا.
     */
    public function getPasswordAttribute($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (!$this->isEncryptedPayload($value)) {
            // قيمة نصّية (Legacy) — تُعاد كما هي لتعمل في تسجيل الدخول.
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException $e) {
            Log::warning('Failed to decrypt NissanCredential.password: '.$e->getMessage());
            // نُعيدها كما هي؛ لكن الأفضل إعادة إدخال كلمة المرور لتُعاد تشفيرها بالمفتاح الحالي.
            return $value;
        }
    }
}

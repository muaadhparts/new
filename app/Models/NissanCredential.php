<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class NissanCredential extends Model
{
    protected $table = 'nissan_credentials';

    // أضف الحقل الجديد هنا
    protected $fillable = ['email', 'password', 'cookie'];

    public $timestamps = true;

    // تشفير تلقائي لكلمة المرور عند القراءة/الكتابة
    public function setPasswordAttribute($value)
    {
        $this->attributes['password'] = Crypt::encryptString($value);
    }

    public function getPasswordAttribute($value)
    {
        return Crypt::decryptString($value);
    }
}

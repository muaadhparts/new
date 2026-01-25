<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Domain\Catalog\Models\NissanCredential;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Contracts\Encryption\DecryptException;

class ReEncryptNissanPasswords extends Command
{
    /**
     * اسم الأمر في Artisan
     */
    protected $signature = 'nissan:re-encrypt-passwords';

    /**
     * الوصف
     */
    protected $description = 'إعادة تشفير كلمات المرور في جدول nissan_credentials لتتوافق مع APP_KEY الحالي';

    public function handle(): int
    {
        $this->info("إعادة تشفير كلمات المرور...");

        $count = 0;

        NissanCredential::chunk(100, function ($credentials) use (&$count) {
            foreach ($credentials as $cred) {
                $original = $cred->getRawOriginal('password');
                $decoded = json_decode(base64_decode($original, true) ?: '', true);

                // إذا القيمة تبدو مشفرة بصيغة لارافيل
                $isEncrypted = is_array($decoded) && isset($decoded['iv'], $decoded['value']);

                try {
                    if ($isEncrypted) {
                        // نجرب نفك تشفيرها بالمفتاح الحالي
                        $decrypted = Crypt::decryptString($original);
                        // إذا نجح → ما نحتاج نعيد
                        continue;
                    } else {
                        // قيمة نصية (قديمة)، نعيد تشفيرها
                        $decrypted = $original;
                    }
                } catch (DecryptException $e) {
                    // فشل الفك → نعاملها كنص عادي
                    $decrypted = $original;
                }

                // الآن نشفرها بمفتاح لارافيل الحالي
                $cred->password = $decrypted;
                $cred->save();

                $count++;
                $this->line("✔️ Re-encrypted ID={$cred->id}");
            }
        });

        $this->info("تمت إعادة تشفير {$count} كلمة مرور.");
        return Command::SUCCESS;
    }
}

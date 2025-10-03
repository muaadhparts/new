<?php

namespace App\Traits;

/**
 * Trait لتطبيع المدخلات (نصوص، أرقام، عربي، إلخ)
 */
trait NormalizesInput
{
    /**
     * تنظيف المدخلات (إزالة المسافات، الشرطات، النقاط)
     */
    protected function cleanInput(?string $input): string
    {
        return strtoupper(preg_replace('/[\s\-.,]+/', '', trim((string) $input)));
    }

    /**
     * تطبيع النصوص العربية (توحيد الهمزات، إزالة التشكيل)
     */
    protected function normalizeArabic(string $text): string
    {
        $replacements = [
            'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا',
            'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي', 'ة' => 'ه',
            'َ' => '', 'ً' => '', 'ُ' => '', 'ٌ' => '',
            'ِ' => '', 'ٍ' => '', 'ْ' => '', 'ّ' => '', 'ٰ' => '',
        ];

        $normalized = strtr($text, $replacements);

        // إزالة الفواصل والنقاط
        $normalized = preg_replace('/[\.\/\\\\\|\:]+/', ' ', $normalized);

        // إزالة الأحرف غير المرغوبة
        $normalized = preg_replace('/[^\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\sA-Za-z0-9\-\(\)\/]/u', '', $normalized);

        return trim($normalized);
    }

    /**
     * تصفية الإدخالات (حماية من XSS)
     */
    protected function sanitizeInput($input): string
    {
        return trim(strip_tags((string)$input));
    }

    /**
     * التحقق من أن كود الكتالوج صالح
     */
    protected function ensureValidCatalogCode($catalogCode): void
    {
        if (!preg_match('/^[A-Za-z0-9_]+$/', (string)$catalogCode)) {
            throw new \Exception('Invalid catalog code');
        }
    }

    /**
     * توليد اسم جدول ديناميكي
     */
    protected function dyn(string $base, string $catalogCode): string
    {
        $this->ensureValidCatalogCode($catalogCode);
        return strtolower("{$base}_{$catalogCode}");
    }
}

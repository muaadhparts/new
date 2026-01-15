<?php

namespace App\Services\GlobalData\Contexts;

/**
 * ContextInterface
 *
 * عقد موحد لكل Context في GlobalData
 */
interface ContextInterface
{
    /**
     * تحميل البيانات
     */
    public function load(): void;

    /**
     * الحصول على البيانات للـ Views
     */
    public function toArray(): array;

    /**
     * إعادة تعيين (للاختبارات)
     */
    public function reset(): void;
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * LocalizationMiddleware
 *
 * @deprecated تم نقل كل الوظائف إلى GlobalDataMiddleware
 *
 * هذا الـ middleware أصبح pass-through فقط للتوافق مع الـ routes القديمة
 * التي تستخدم 'localization' middleware alias.
 *
 * GlobalDataMiddleware يقوم الآن بـ:
 * - تحميل اللغة والعملة
 * - تعيين locale
 * - مشاركة البيانات مع الـ views
 */
class LocalizationMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @deprecated Use GlobalDataMiddleware instead
     */
    public function handle(Request $request, Closure $next): Response
    {
        // تم نقل كل المنطق إلى GlobalDataMiddleware
        // هذا الـ middleware يمرر الـ request فقط للتوافق
        return $next($request);
    }
}

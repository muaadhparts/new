<?php

namespace App\Http\Middleware;

use App\Services\GlobalData\GlobalDataService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/**
 * GlobalDataMiddleware
 *
 * يحمّل كل البيانات المشتركة مرة واحدة في بداية الـ request
 * ويشاركها مع كل الـ views.
 *
 * هذا يحل محل:
 * - view()->composer('*', ...) في AppServiceProvider
 * - view()->composer('*', ...) في SeoServiceProvider
 * - LocalizationMiddleware
 *
 * المبدأ المعماري:
 * - Middleware يُنفَّذ مرة واحدة لكل request
 * - view()->share() يجعل البيانات متاحة لكل الـ views
 * - GlobalDataService (singleton) يضمن عدم تكرار التحميل
 */
class GlobalDataMiddleware
{
    public function __construct(
        private GlobalDataService $globalDataService
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. تحميل كل البيانات مرة واحدة
        $this->globalDataService->load();

        // 2. مشاركة البيانات مع كل الـ views
        view()->share($this->globalDataService->getAllForViews());

        // 3. تعيين اللغة للتطبيق
        $language = $this->globalDataService->getLanguage();
        if ($language) {
            app()->setLocale($language->name);
        }

        // 4. معالجة الـ Popup (من LocalizationMiddleware القديم)
        if (!Session::has('popup')) {
            view()->share('visited', 1);
        }
        Session::put('popup', 1);

        return $next($request);
    }
}

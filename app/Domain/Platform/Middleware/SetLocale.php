<?php

namespace App\Domain\Platform\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Set Locale Middleware
 *
 * Sets the application locale based on user preference or session.
 */
class SetLocale
{
    /**
     * Supported locales
     */
    protected array $supportedLocales = ['ar', 'en'];

    /**
     * Default locale
     */
    protected string $defaultLocale = 'ar';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->determineLocale($request);

        app()->setLocale($locale);
        session(['locale' => $locale]);

        return $next($request);
    }

    /**
     * Determine the locale to use
     */
    protected function determineLocale(Request $request): string
    {
        // 1. Check query parameter
        if ($request->has('lang') && $this->isSupported($request->get('lang'))) {
            return $request->get('lang');
        }

        // 2. Check authenticated user preference
        if ($user = $request->user()) {
            if ($user->language && $this->isSupported($user->language)) {
                return $user->language;
            }
        }

        // 3. Check session
        if (session()->has('locale') && $this->isSupported(session('locale'))) {
            return session('locale');
        }

        // 4. Check browser Accept-Language header
        $browserLocale = $request->getPreferredLanguage($this->supportedLocales);
        if ($browserLocale && $this->isSupported($browserLocale)) {
            return $browserLocale;
        }

        // 5. Fall back to default
        return $this->defaultLocale;
    }

    /**
     * Check if locale is supported
     */
    protected function isSupported(string $locale): bool
    {
        return in_array($locale, $this->supportedLocales);
    }
}

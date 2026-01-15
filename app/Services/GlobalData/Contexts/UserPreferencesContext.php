<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\Currency;
use App\Models\Language;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

/**
 * UserPreferencesContext
 *
 * تفضيلات المستخدم:
 * - العملة (من Session أو الافتراضية)
 * - اللغة (من Session أو الافتراضية)
 */
class UserPreferencesContext implements ContextInterface
{
    private ?Currency $currency = null;
    private ?Language $language = null;

    public function load(): void
    {
        $this->currency = $this->resolveCurrency();
        $this->language = $this->resolveLanguage();
    }

    private function resolveCurrency(): ?Currency
    {
        if (Session::has('currency')) {
            $id = Session::get('currency');
            return Cache::remember("currency_{$id}", 3600, fn() =>
                Currency::find($id)
            );
        }

        return Cache::remember('default_currency', 3600, fn() =>
            Currency::where('is_default', 1)->first()
        );
    }

    private function resolveLanguage(): ?Language
    {
        if (Session::has('language')) {
            $id = Session::get('language');
            return Cache::remember("language_{$id}", 3600, fn() =>
                Language::find($id)
            );
        }

        return Cache::remember('default_language', 3600, fn() =>
            Language::where('is_default', 1)->first()
        );
    }

    public function toArray(): array
    {
        return [
            'curr' => $this->currency,
            'langg' => $this->language,
        ];
    }

    public function reset(): void
    {
        $this->currency = null;
        $this->language = null;
    }

    // === Getters ===

    public function getCurrency(): ?Currency
    {
        return $this->currency;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }
}

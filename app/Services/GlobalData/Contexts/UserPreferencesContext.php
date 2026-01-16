<?php

namespace App\Services\GlobalData\Contexts;

use App\Models\MonetaryUnit;
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
    private ?MonetaryUnit $monetaryUnit = null;
    private ?Language $language = null;

    public function load(): void
    {
        $this->monetaryUnit = $this->resolveMonetaryUnit();
        $this->language = $this->resolveLanguage();
    }

    private function resolveMonetaryUnit(): ?MonetaryUnit
    {
        if (Session::has('monetary_unit')) {
            $id = Session::get('monetary_unit');
            return Cache::remember("monetary_unit_{$id}", 3600, fn() =>
                MonetaryUnit::find($id)
            );
        }

        return monetaryUnit()->getDefault();
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
            'curr' => $this->monetaryUnit,
            'langg' => $this->language,
        ];
    }

    public function reset(): void
    {
        $this->monetaryUnit = null;
        $this->language = null;
    }

    // === Getters ===

    public function getMonetaryUnit(): ?MonetaryUnit
    {
        return $this->monetaryUnit;
    }

    public function getLanguage(): ?Language
    {
        return $this->language;
    }
}

<?php

namespace App\Domain\Platform\Services\GlobalData;

use App\Services\GlobalData\Contexts\CoreSettingsContext;
use App\Services\GlobalData\Contexts\ExternalApisContext;
use App\Services\GlobalData\Contexts\FooterContext;
use App\Services\GlobalData\Contexts\NavigationContext;
use App\Services\GlobalData\Contexts\SeoContext;
use App\Services\GlobalData\Contexts\UserPreferencesContext;

/**
 * GlobalDataService - Orchestrator
 *
 * ينسّق تحميل البيانات من كل الـ Contexts.
 * لا يحتوي على منطق تحميل - فقط ينسّق بين الـ Contexts.
 *
 * البنية:
 * ├── CoreSettingsContext     → إعدادات النظام
 * ├── UserPreferencesContext  → تفضيلات المستخدم
 * ├── NavigationContext       → بيانات التنقل
 * ├── FooterContext           → بيانات الفوتر
 * ├── ExternalApisContext     → مفاتيح API
 * └── SeoContext              → SEO Schemas
 */
class GlobalDataService
{
    private bool $loaded = false;

    private CoreSettingsContext $coreSettings;
    private UserPreferencesContext $userPreferences;
    private NavigationContext $navigation;
    private FooterContext $footer;
    private ExternalApisContext $externalApis;
    private SeoContext $seo;

    public function __construct()
    {
        $this->coreSettings = new CoreSettingsContext();
        $this->userPreferences = new UserPreferencesContext();
        $this->navigation = new NavigationContext();
        $this->footer = new FooterContext();
        $this->externalApis = new ExternalApisContext();
        $this->seo = new SeoContext($this->coreSettings);
    }

    /**
     * تحميل كل البيانات مرة واحدة
     */
    public function load(): void
    {
        if ($this->loaded) {
            return;
        }

        // تحميل بالترتيب (SEO يعتمد على CoreSettings)
        $this->coreSettings->load();
        $this->userPreferences->load();
        $this->navigation->load();
        $this->footer->load();
        $this->externalApis->load();
        $this->seo->load();

        $this->loaded = true;
    }

    /**
     * الحصول على كل البيانات للـ Views
     */
    public function getAllForViews(): array
    {
        return array_merge(
            $this->coreSettings->toArray(),
            $this->userPreferences->toArray(),
            $this->navigation->toArray(),
            $this->footer->toArray(),
            $this->externalApis->toArray(),
            $this->seo->toArray()
        );
    }

    // ==========================================
    // CONTEXT ACCESSORS
    // ==========================================

    public function coreSettings(): CoreSettingsContext
    {
        return $this->coreSettings;
    }

    public function userPreferences(): UserPreferencesContext
    {
        return $this->userPreferences;
    }

    public function navigation(): NavigationContext
    {
        return $this->navigation;
    }

    public function footer(): FooterContext
    {
        return $this->footer;
    }

    public function externalApis(): ExternalApisContext
    {
        return $this->externalApis;
    }

    public function seo(): SeoContext
    {
        return $this->seo;
    }

    // ==========================================
    // CONVENIENCE GETTERS (للتوافق مع الكود القديم)
    // ==========================================

    public function getSettings(): ?object
    {
        return $this->coreSettings->getSettings();
    }

    public function getMonetaryUnit(): ?\App\Models\MonetaryUnit
    {
        return $this->userPreferences->getMonetaryUnit();
    }

    public function getLanguage(): ?\App\Models\Language
    {
        return $this->userPreferences->getLanguage();
    }

    public function getBrands()
    {
        return $this->navigation->getBrands();
    }

    // ==========================================
    // STATE
    // ==========================================

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function reset(): void
    {
        $this->loaded = false;

        $this->coreSettings->reset();
        $this->userPreferences->reset();
        $this->navigation->reset();
        $this->footer->reset();
        $this->externalApis->reset();
        $this->seo->reset();
    }
}

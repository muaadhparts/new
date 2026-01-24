<?php

namespace Tests\Regression\Platform;

use Tests\TestCase;
use App\Models\Language;
use App\Models\MonetaryUnit;
use App\Models\HomePageTheme;
use App\Models\FrontendSetting;
use App\Models\PlatformSetting;
use App\Domain\Platform\Models\Language as DomainLanguage;
use App\Domain\Platform\Models\MonetaryUnit as DomainMonetaryUnit;
use App\Domain\Platform\Models\HomePageTheme as DomainHomePageTheme;

class PlatformModelsTest extends TestCase
{
    /**
     * Test that old model paths still work (backward compatibility)
     */
    public function test_old_model_paths_work(): void
    {
        // These should not throw exceptions
        $language = Language::first();
        $this->assertNotNull($language);

        $monetaryUnit = MonetaryUnit::first();
        $this->assertNotNull($monetaryUnit);

        $theme = HomePageTheme::first();
        $this->assertNotNull($theme);
    }

    /**
     * Test that old models are instances of new Domain models
     */
    public function test_old_models_extend_domain_models(): void
    {
        $language = Language::first();
        $this->assertInstanceOf(DomainLanguage::class, $language);

        $monetaryUnit = MonetaryUnit::first();
        $this->assertInstanceOf(DomainMonetaryUnit::class, $monetaryUnit);

        $theme = HomePageTheme::first();
        $this->assertInstanceOf(DomainHomePageTheme::class, $theme);
    }

    /**
     * Test Language model functionality
     */
    public function test_language_model_works(): void
    {
        $language = Language::where('is_default', true)->first();

        $this->assertNotNull($language);
        $this->assertIsString($language->language);
        $this->assertIsBool($language->isRtl());
    }

    /**
     * Test MonetaryUnit model functionality
     */
    public function test_monetary_unit_model_works(): void
    {
        $unit = MonetaryUnit::where('is_default', 1)->first();

        $this->assertNotNull($unit);
        $this->assertIsString($unit->name);
        $this->assertIsString($unit->sign);

        // Test conversion methods
        $converted = $unit->fromBaseMonetaryUnit(100);
        $this->assertIsFloat($converted);

        $formatted = $unit->formatAmount(100);
        $this->assertIsString($formatted);
    }

    /**
     * Test HomePageTheme model functionality
     */
    public function test_home_page_theme_model_works(): void
    {
        $theme = HomePageTheme::getActive();

        $this->assertNotNull($theme);
        $this->assertIsBool($theme->is_active);
        $this->assertIsBool($theme->show_brands);
        $this->assertIsBool($theme->show_categories);

        $sections = $theme->getOrderedSections();
        $this->assertIsArray($sections);
    }

    /**
     * Test MonetaryUnitService functionality
     */
    public function test_monetary_unit_service_works(): void
    {
        $service = app(\App\Services\MonetaryUnitService::class);

        $this->assertNotNull($service->getCurrent());
        $this->assertNotNull($service->getDefault());
        $this->assertIsString($service->getSign());
        $this->assertIsString($service->getCode());

        $formatted = $service->format(100);
        $this->assertIsString($formatted);

        $converted = $service->convert(100);
        $this->assertIsFloat($converted);
    }

    /**
     * Test monetaryUnit() helper still works
     */
    public function test_monetary_unit_helper_works(): void
    {
        $result = monetaryUnit()->format(100);
        $this->assertIsString($result);

        $result = monetaryUnit()->convertAndFormat(100);
        $this->assertIsString($result);
    }
}

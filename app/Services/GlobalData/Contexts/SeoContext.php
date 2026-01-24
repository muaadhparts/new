<?php

namespace App\Services\GlobalData\Contexts;

use App\Services\SEO\Schema\OrganizationSchema;
use App\Services\SEO\Schema\WebsiteSchema;

/**
 * SeoContext
 *
 * بيانات SEO:
 * - Organization Schema
 * - Website Schema
 *
 * يعتمد على CoreSettingsContext للحصول على الإعدادات
 */
class SeoContext implements ContextInterface
{
    private ?OrganizationSchema $organizationSchema = null;
    private ?WebsiteSchema $websiteSchema = null;

    private CoreSettingsContext $coreSettings;

    public function __construct(CoreSettingsContext $coreSettings)
    {
        $this->coreSettings = $coreSettings;
    }

    public function load(): void
    {
        $settings = $this->coreSettings->getSettings();

        if (!$settings) {
            return;
        }

        $this->organizationSchema = OrganizationSchema::fromSettings(
            $settings,
            $this->coreSettings->getSeoSettings(),
            $this->coreSettings->getSocialLinks()
        );

        $this->websiteSchema = WebsiteSchema::fromSettings($settings);
    }

    public function toArray(): array
    {
        return [
            'organizationSchema' => $this->organizationSchema,
            'websiteSchema' => $this->websiteSchema,
            'globalSchemasLoaded' => true,
        ];
    }

    public function reset(): void
    {
        $this->organizationSchema = null;
        $this->websiteSchema = null;
    }

    // === Getters ===

    public function getOrganizationSchema(): ?OrganizationSchema
    {
        return $this->organizationSchema;
    }

    public function getWebsiteSchema(): ?WebsiteSchema
    {
        return $this->websiteSchema;
    }
}

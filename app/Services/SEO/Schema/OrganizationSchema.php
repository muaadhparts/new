<?php

namespace App\Services\SEO\Schema;

/**
 * Organization Schema Builder
 */
class OrganizationSchema extends SchemaBuilder
{
    protected string $name = '';
    protected string $url = '';
    protected string $logo = '';
    protected string $description = '';
    protected array $socialLinks = [];
    protected array $contactPoint = [];

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function setSocialLinks(array $links): self
    {
        $this->socialLinks = array_filter($links);
        return $this;
    }

    public function setContactPoint(string $type = 'customer service', array $languages = ['Arabic', 'English']): self
    {
        $this->contactPoint = [
            '@type' => 'ContactPoint',
            'contactType' => $type,
            'availableLanguage' => $languages
        ];
        return $this;
    }

    public function build(): self
    {
        $this->setContext();
        $this->setType('Organization');

        $this->data['name'] = $this->name;
        $this->data['url'] = $this->url;

        if ($this->logo) {
            $this->data['logo'] = $this->logo;
        }

        if ($this->description) {
            $this->data['description'] = $this->description;
        }

        if (!empty($this->contactPoint)) {
            $this->data['contactPoint'] = $this->contactPoint;
        }

        if (!empty($this->socialLinks)) {
            $this->data['sameAs'] = array_values($this->socialLinks);
        }

        return $this;
    }

    /**
     * Create from global settings
     */
    public static function fromSettings($gs, $seo = null, $social = null): self
    {
        $schema = self::create()
            ->setName($gs->title ?? config('app.name'))
            ->setUrl(url('/'))
            ->setLogo(asset('assets/images/' . ($gs->logo ?? 'logo.png')))
            ->setContactPoint();

        if ($seo && !empty($seo->meta_description)) {
            $schema->setDescription($seo->meta_description);
        }

        if ($social) {
            $schema->setSocialLinks([
                $social->facebook ?? null,
                $social->twitter ?? null,
                $social->instagram ?? null,
                $social->youtube ?? null,
                $social->linkedin ?? null,
            ]);
        }

        return $schema->build();
    }

    public function toArray(): array
    {
        if (empty($this->data)) {
            $this->build();
        }
        return $this->data;
    }
}

<?php

namespace App\Services\SEO\Schema;

/**
 * WebSite Schema with SearchAction
 */
class WebsiteSchema extends SchemaBuilder
{
    protected string $name = '';
    protected string $url = '';
    protected string $searchUrl = '';

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

    public function setSearchUrl(string $searchUrl): self
    {
        $this->searchUrl = $searchUrl;
        return $this;
    }

    public function build(): self
    {
        $this->setContext();
        $this->setType('WebSite');

        $this->data['name'] = $this->name;
        $this->data['url'] = $this->url;

        if ($this->searchUrl) {
            $this->data['potentialAction'] = [
                '@type' => 'SearchAction',
                'target' => [
                    '@type' => 'EntryPoint',
                    'urlTemplate' => $this->searchUrl
                ],
                'query-input' => 'required name=search_term_string'
            ];
        }

        return $this;
    }

    /**
     * Create from global settings
     */
    public static function fromSettings($gs): self
    {
        return self::create()
            ->setName($gs->title ?? config('app.name'))
            ->setUrl(url('/'))
            ->setSearchUrl(url('/category?search={search_term_string}'))
            ->build();
    }

    public function toArray(): array
    {
        if (empty($this->data)) {
            $this->build();
        }
        return $this->data;
    }
}

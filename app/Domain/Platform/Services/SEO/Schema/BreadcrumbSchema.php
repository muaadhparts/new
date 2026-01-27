<?php

namespace App\Domain\Platform\Services\SEO\Schema;

/**
 * BreadcrumbList Schema Builder
 */
class BreadcrumbSchema extends SchemaBuilder
{
    protected array $items = [];

    /**
     * Add breadcrumb item
     */
    public function addItem(string $name, string $url, int $position = null): self
    {
        $position = $position ?? count($this->items) + 1;

        $this->items[] = [
            '@type' => 'ListItem',
            'position' => $position,
            'name' => $name,
            'item' => $url
        ];

        return $this;
    }

    /**
     * Build breadcrumb for catalog item page
     */
    public function forCatalogItem($catalogItem): self
    {
        $this->addItem(__('Home'), url('/'));
        $this->addItem(__('Catalog'), route('front.catalog'));

        if ($catalogItem->brand) {
            $this->addItem(
                $catalogItem->brand->name,
                route('front.catalog', ['category' => $catalogItem->brand->slug])
            );
        }

        $this->addItem($catalogItem->name, url()->current());

        return $this->build();
    }

    /**
     * Build breadcrumb for category page
     */
    public function forCategory($category, $parent = null): self
    {
        $this->addItem(__('Home'), url('/'));
        $this->addItem(__('Catalog'), route('front.catalog'));

        if ($parent) {
            $this->addItem($parent->name, route('front.catalog', ['category' => $parent->slug]));
        }

        $this->addItem($category->name, route('front.catalog', ['category' => $category->slug]));

        return $this->build();
    }

    public function build(): self
    {
        $this->setContext();
        $this->setType('BreadcrumbList');
        $this->data['itemListElement'] = $this->items;

        return $this;
    }

    public function toArray(): array
    {
        if (empty($this->data)) {
            $this->build();
        }
        return $this->data;
    }
}

<?php

namespace App\Domain\Platform\Services\SEO;

use App\Domain\Platform\Services\SEO\Schema\SchemaBuilder;
use App\Domain\Platform\Services\SEO\Schema\CatalogItemSchema;
use App\Domain\Platform\Services\SEO\Schema\OrganizationSchema;
use App\Domain\Platform\Services\SEO\Schema\BreadcrumbSchema;
use App\Domain\Platform\Services\SEO\Schema\WebsiteSchema;
use Illuminate\Support\Facades\Cache;

/**
 * SEO Service - Central SEO management layer
 * يدير كل منطق SEO بشكل منفصل عن الـ Views
 */
class SeoService
{
    protected array $schemas = [];
    protected array $meta = [];
    protected ?string $canonical = null;
    protected array $alternates = [];

    /**
     * Set page meta data
     */
    public function setMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    /**
     * Set canonical URL with smart multi-merchant handling
     */
    public function setCanonical(string $url): self
    {
        $this->canonical = $url;
        return $this;
    }

    /**
     * Smart canonical for products with multiple merchants
     * يختار الـ Canonical الأمثل بناءً على: السعر الأقل، التقييم الأعلى، أو المخزون
     */
    public function setProductCanonical($catalogItem, $currentMerchantItem = null, string $strategy = 'lowest_price'): self
    {
        $merchantItems = $catalogItem->merchantItems()
            ->where('status', 1)
            ->with('user')
            ->get();

        if ($merchantItems->isEmpty()) {
            return $this;
        }

        // Set canonical URL - one page per part_number
        if ($catalogItem->part_number) {
            $this->canonical = route('front.part-result', $catalogItem->part_number);
        } else {
            $this->canonical = url()->current();
        }

        // No alternates needed - one canonical URL per part number

        return $this;
    }

    /**
     * Add a schema to the page
     */
    public function addSchema(SchemaBuilder $schema): self
    {
        $this->schemas[] = $schema;
        return $this;
    }

    /**
     * Build product page SEO
     */
    public function forProduct($catalogItem, $merchantItem, $currency = 'SAR'): self
    {
        // Set smart canonical
        $this->setProductCanonical($catalogItem, $merchantItem, 'lowest_price');

        // Add CatalogItem Schema
        $this->addSchema(
            CatalogItemSchema::create()
                ->setCatalogItem($catalogItem)
                ->setMerchant($merchantItem)
                ->setCurrency($currency)
        );

        // Add Breadcrumb Schema
        $this->addSchema(
            BreadcrumbSchema::create()
                ->forProduct($catalogItem)
        );

        // Set meta
        $this->setMeta([
            'name' => $catalogItem->name . ' - ' . config('app.name'),
            'description' => $catalogItem->meta_description ?? \Str::limit(strip_tags($catalogItem->description ?? ''), 160),
            'keywords' => $catalogItem->meta_tag ?? '',
            'og:type' => 'product',
            'og:name' => $catalogItem->name,
            'og:description' => $catalogItem->meta_description ?? \Str::limit(strip_tags($catalogItem->description ?? ''), 200),
            'og:image' => $this->getProductImage($catalogItem),
            'product:price:amount' => $merchantItem->merchantSizePrice(),
            'product:price:currency' => $currency,
            'product:availability' => ($merchantItem->stock > 0 || is_null($merchantItem->stock)) ? 'in stock' : 'out of stock',
        ]);

        return $this;
    }

    /**
     * Build category page SEO
     */
    public function forCategory($category): self
    {
        $this->setCanonical(route('front.catalog', ['category' => $category->slug]));

        $this->addSchema(
            BreadcrumbSchema::create()
                ->forCategory($category)
        );

        $this->setMeta([
            'name' => $category->name . ' - ' . config('app.name'),
            'description' => $category->meta_description ?? "تصفح منتجات {$category->name}",
            'og:type' => 'website',
            'og:name' => $category->name,
        ]);

        return $this;
    }

    /**
     * Get canonical URL
     */
    public function getCanonical(): ?string
    {
        return $this->canonical;
    }

    /**
     * Get alternate URLs
     */
    public function getAlternates(): array
    {
        return $this->alternates;
    }

    /**
     * Get meta tags
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    /**
     * Render all schemas as JSON-LD
     */
    public function renderSchemas(): string
    {
        $output = '';
        foreach ($this->schemas as $schema) {
            $output .= $schema->toScript() . "\n";
        }
        return $output;
    }

    /**
     * Render meta tags
     */
    public function renderMeta(): string
    {
        $output = '';

        // Canonical
        if ($this->canonical) {
            $output .= '<link rel="canonical" href="' . e($this->canonical) . '">' . "\n";
        }

        // Standard meta
        foreach ($this->meta as $key => $value) {
            if (empty($value)) continue;

            if (str_starts_with($key, 'og:') || str_starts_with($key, 'product:')) {
                $output .= '<meta property="' . e($key) . '" content="' . e($value) . '">' . "\n";
            } elseif (str_starts_with($key, 'twitter:')) {
                $output .= '<meta name="' . e($key) . '" content="' . e($value) . '">' . "\n";
            } elseif ($key === 'name') {
                // Name is handled separately in <name> tag
                continue;
            } else {
                $output .= '<meta name="' . e($key) . '" content="' . e($value) . '">' . "\n";
            }
        }

        return $output;
    }

    /**
     * Get page name
     */
    public function getName(): string
    {
        return $this->meta['name'] ?? config('app.name');
    }

    /**
     * Get product image URL
     */
    protected function getProductImage($catalogItem): string
    {
        if (!$catalogItem->photo) {
            return asset('assets/images/noimage.png');
        }

        if (filter_var($catalogItem->photo, FILTER_VALIDATE_URL)) {
            return $catalogItem->photo;
        }

        return \Storage::url($catalogItem->photo);
    }
}

<?php

namespace App\Services\SEO\Schema;

use Carbon\Carbon;

/**
 * CatalogItem Schema Builder
 * يبني Schema.org Product بشكل صحيح
 */
class CatalogItemSchema extends SchemaBuilder
{
    protected $catalogItem;
    protected $merchant;
    protected string $currency = 'SAR';

    public function setCatalogItem($catalogItem): self
    {
        $this->catalogItem = $catalogItem;
        return $this;
    }

    public function setMerchant($merchant): self
    {
        $this->merchant = $merchant;
        return $this;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;
        return $this;
    }

    public function build(): self
    {
        if (!$this->catalogItem || !$this->merchant) {
            return $this;
        }

        $this->setContext();
        $this->setType('Product');

        // Basic catalog item info
        $this->data['name'] = $this->catalogItem->name;
        $this->data['description'] = $this->getDescription();
        $this->data['image'] = $this->getImage();
        $this->data['sku'] = $this->catalogItem->part_number ?? $this->catalogItem->sku ?? '';
        $this->data['mpn'] = $this->catalogItem->part_number ?? '';

        // Brand (from catalog item fitments - OEM brand)
        $fitments = $this->catalogItem->fitments ?? collect();
        $brands = $fitments->map(fn($f) => $f->brand)->filter()->unique('id')->values();
        $firstBrand = $brands->first();

        if ($firstBrand) {
            $this->data['brand'] = [
                '@type' => 'Brand',
                'name' => $firstBrand->name
            ];
            $this->data['category'] = $firstBrand->name;
        } elseif ($this->merchant->qualityBrand) {
            // Fallback to quality brand if no OEM brand
            $this->data['brand'] = [
                '@type' => 'Brand',
                'name' => $this->merchant->qualityBrand->name
            ];
            $this->data['category'] = $this->merchant->qualityBrand->name;
        }

        // Offers
        $this->data['offers'] = $this->buildOffers();

        // Aggregate Rating (if available)
        if ($this->catalogItem->reviews_count ?? false) {
            $this->data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->catalogItem->average_rating ?? 4,
                'reviewCount' => $this->catalogItem->reviews_count,
                'bestRating' => 5,
                'worstRating' => 1
            ];
        }

        return $this;
    }

    protected function buildOffers(): array
    {
        $url = $this->catalogItem->part_number
            ? route('front.part-result', $this->catalogItem->part_number)
            : url()->current();

        return [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => $this->currency,
            'price' => number_format($this->merchant->merchantSizePrice(), 2, '.', ''),
            'availability' => $this->getAvailability(),
            'itemCondition' => 'https://schema.org/NewCondition',
            'priceValidUntil' => Carbon::now()->addYear()->format('Y-m-d'),
            'seller' => [
                '@type' => 'Organization',
                'name' => $this->merchant->user->shop_name ?? $this->merchant->user->name ?? 'Merchant'
            ]
        ];
    }

    protected function getDescription(): string
    {
        $desc = $this->catalogItem->meta_description
            ?? strip_tags($this->catalogItem->description ?? '')
            ?? $this->catalogItem->name;

        return \Str::limit($desc, 500);
    }

    protected function getImage(): string
    {
        if (!$this->catalogItem->photo) {
            return asset('assets/images/noimage.png');
        }

        if (filter_var($this->catalogItem->photo, FILTER_VALIDATE_URL)) {
            return $this->catalogItem->photo;
        }

        return \Storage::url($this->catalogItem->photo);
    }

    protected function getAvailability(): string
    {
        if ($this->merchant->stock > 0 || is_null($this->merchant->stock)) {
            return 'https://schema.org/InStock';
        }
        return 'https://schema.org/OutOfStock';
    }

    public function toArray(): array
    {
        if (empty($this->data)) {
            $this->build();
        }
        return $this->data;
    }

    public function toJson(): string
    {
        if (empty($this->data)) {
            $this->build();
        }
        return parent::toJson();
    }
}

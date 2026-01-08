<?php

namespace App\Services\SEO\Schema;

use Carbon\Carbon;

/**
 * Product Schema Builder
 * يبني Schema.org Product بشكل صحيح
 */
class ProductSchema extends SchemaBuilder
{
    protected $product;
    protected $merchant;
    protected string $currency = 'SAR';

    public function setProduct($product): self
    {
        $this->product = $product;
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
        if (!$this->product || !$this->merchant) {
            return $this;
        }

        $this->setContext();
        $this->setType('Product');

        // Basic product info
        $this->data['name'] = $this->product->name;
        $this->data['description'] = $this->getDescription();
        $this->data['image'] = $this->getImage();
        $this->data['sku'] = $this->product->part_number ?? $this->product->sku ?? '';
        $this->data['mpn'] = $this->product->part_number ?? '';

        // Brand
        if ($this->product->brand) {
            $this->data['brand'] = [
                '@type' => 'Brand',
                'name' => $this->product->brand->name
            ];
            $this->data['category'] = $this->product->brand->name;
        }

        // Offers
        $this->data['offers'] = $this->buildOffers();

        // Aggregate Rating (if available)
        if ($this->product->reviews_count ?? false) {
            $this->data['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $this->product->average_rating ?? 4,
                'reviewCount' => $this->product->reviews_count,
                'bestRating' => 5,
                'worstRating' => 1
            ];
        }

        return $this;
    }

    protected function buildOffers(): array
    {
        $url = route('front.catalog-item', [
            'slug' => $this->product->slug,
            'merchant_id' => $this->merchant->user_id,
            'merchant_item_id' => $this->merchant->id
        ]);

        return [
            '@type' => 'Offer',
            'url' => $url,
            'priceCurrency' => $this->currency,
            'price' => number_format($this->merchant->price, 2, '.', ''),
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
        $desc = $this->product->meta_description
            ?? strip_tags($this->product->description ?? '')
            ?? $this->product->name;

        return \Str::limit($desc, 500);
    }

    protected function getImage(): string
    {
        if (!$this->product->photo) {
            return asset('assets/images/noimage.png');
        }

        if (filter_var($this->product->photo, FILTER_VALIDATE_URL)) {
            return $this->product->photo;
        }

        return \Storage::url($this->product->photo);
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

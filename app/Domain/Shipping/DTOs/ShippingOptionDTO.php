<?php

namespace App\Domain\Shipping\DTOs;

/**
 * ShippingOptionDTO - Represents a shipping option for checkout
 *
 * Used to display shipping options to customers during checkout.
 */
class ShippingOptionDTO
{
    public int $id;
    public string $name;
    public string $nameAr;
    public string $provider;
    public float $price;
    public string $priceFormatted;
    public ?string $estimatedDays;
    public bool $isFree;
    public ?string $logo;
    public ?string $description;

    /**
     * Create from Shipping model
     */
    public static function fromModel($shipping, float $calculatedPrice = null): self
    {
        $dto = new self();

        $dto->id = $shipping->id;
        $dto->name = $shipping->title ?? '';
        $dto->nameAr = $shipping->title_ar ?? $dto->name;
        $dto->provider = $shipping->provider ?? 'manual';
        $dto->price = $calculatedPrice ?? (float) $shipping->price;
        $dto->priceFormatted = monetaryUnit()->format($dto->price);
        $dto->estimatedDays = $shipping->estimated_days ?? null;
        $dto->isFree = $dto->price <= 0;
        $dto->logo = $shipping->logo ?? null;
        $dto->description = $shipping->description ?? null;

        return $dto;
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $dto = new self();

        $dto->id = (int) ($data['id'] ?? 0);
        $dto->name = $data['name'] ?? '';
        $dto->nameAr = $data['name_ar'] ?? $dto->name;
        $dto->provider = $data['provider'] ?? 'manual';
        $dto->price = (float) ($data['price'] ?? 0);
        $dto->priceFormatted = $data['price_formatted'] ?? monetaryUnit()->format($dto->price);
        $dto->estimatedDays = $data['estimated_days'] ?? null;
        $dto->isFree = (bool) ($data['is_free'] ?? $dto->price <= 0);
        $dto->logo = $data['logo'] ?? null;
        $dto->description = $data['description'] ?? null;

        return $dto;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_ar' => $this->nameAr,
            'provider' => $this->provider,
            'price' => $this->price,
            'price_formatted' => $this->priceFormatted,
            'estimated_days' => $this->estimatedDays,
            'is_free' => $this->isFree,
            'logo' => $this->logo,
            'description' => $this->description,
        ];
    }

    /**
     * Get localized name
     */
    public function getLocalizedName(): string
    {
        return app()->getLocale() === 'ar' && $this->nameAr
            ? $this->nameAr
            : $this->name;
    }

    /**
     * Get display price text
     */
    public function getDisplayPrice(): string
    {
        return $this->isFree ? __('Free') : $this->priceFormatted;
    }
}

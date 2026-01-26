<?php

namespace App\Domain\Merchant\DTOs;

use App\Domain\Identity\Models\User;

/**
 * MerchantCardDTO - Pre-computed data for merchant card display
 *
 * DATA FLOW POLICY: Views must only read properties, no logic, no queries
 */
final class MerchantCardDTO
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $slug,
        public readonly string $photoUrl,
        public readonly string $detailsUrl,
        public readonly ?string $shopDescription,
        public readonly float $rating,
        public readonly int $reviewsCount,
        public readonly int $itemsCount,
        public readonly bool $isVerified,
        public readonly ?string $cityName,
        public readonly ?string $countryName,
        public readonly string $memberSince,
    ) {}

    /**
     * Build DTO from User (merchant) model
     */
    public static function fromModel(User $merchant): self
    {
        $locale = app()->getLocale();

        return new self(
            id: $merchant->id,
            name: $locale === 'ar'
                ? ($merchant->shop_name_ar ?: $merchant->shop_name ?: $merchant->name)
                : ($merchant->shop_name ?: $merchant->name),
            slug: $merchant->shop_slug ?? '',
            photoUrl: $merchant->photo_url ?? asset('assets/images/avatar.png'),
            detailsUrl: route('front.merchant', $merchant->shop_slug ?? $merchant->id),
            shopDescription: $locale === 'ar'
                ? ($merchant->shop_description_ar ?: $merchant->shop_description)
                : $merchant->shop_description,
            rating: (float) ($merchant->reviews_avg_rating ?? 0),
            reviewsCount: (int) ($merchant->reviews_count ?? 0),
            itemsCount: (int) ($merchant->merchant_items_count ?? 0),
            isVerified: (bool) ($merchant->is_verified ?? false),
            cityName: $merchant->city ?? null,
            countryName: $merchant->country ?? null,
            memberSince: $merchant->created_at?->format('Y') ?? '',
        );
    }

    /**
     * Build collection of DTOs from User collection
     */
    public static function fromCollection($merchants): array
    {
        return $merchants->map(fn($merchant) => self::fromModel($merchant))->toArray();
    }
}

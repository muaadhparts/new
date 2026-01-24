<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Platform\Traits\HasSlug;
use App\Domain\Platform\Traits\HasStatus;
use App\Domain\Platform\Traits\HasTimestamps;
use App\Domain\Platform\Traits\Searchable;
use App\Domain\Merchant\Traits\BelongsToMerchant;
use App\Domain\Merchant\Traits\HasPricing;
use App\Domain\Merchant\Traits\HasStock;
use App\Domain\Catalog\Traits\HasRating;
use App\Domain\Catalog\Traits\HasImages;
use App\Domain\Commerce\Traits\HasCart;
use App\Domain\Commerce\Traits\HasPurchases;
use App\Domain\Shipping\Traits\HasAddress;
use App\Domain\Shipping\Traits\Trackable;
use App\Domain\Identity\Traits\HasRoles;
use App\Domain\Accounting\Traits\HasBalance;

/**
 * Phase 26: Domain Traits Tests
 *
 * Tests for reusable traits across domains.
 */
class DomainTraitsTest extends TestCase
{
    // ============================================
    // Platform Domain Traits
    // ============================================

    /** @test */
    public function has_slug_trait_exists()
    {
        $this->assertTrue(trait_exists(HasSlug::class));
    }

    /** @test */
    public function has_slug_trait_has_required_methods()
    {
        $methods = ['generateSlug', 'getSlugColumn', 'getSlugSourceColumn', 'findBySlug'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasSlug::class, $method),
                "HasSlug should have {$method} method"
            );
        }
    }

    /** @test */
    public function has_status_trait_exists()
    {
        $this->assertTrue(trait_exists(HasStatus::class));
    }

    /** @test */
    public function has_status_trait_has_required_methods()
    {
        $methods = ['isActive', 'isInactive', 'activate', 'deactivate', 'toggleStatus'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasStatus::class, $method),
                "HasStatus should have {$method} method"
            );
        }
    }

    /** @test */
    public function has_timestamps_trait_exists()
    {
        $this->assertTrue(trait_exists(HasTimestamps::class));
    }

    /** @test */
    public function has_timestamps_trait_has_required_methods()
    {
        $methods = ['wasCreatedToday', 'wasUpdatedToday', 'isNew'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasTimestamps::class, $method),
                "HasTimestamps should have {$method} method"
            );
        }
    }

    /** @test */
    public function searchable_trait_exists()
    {
        $this->assertTrue(trait_exists(Searchable::class));
    }

    /** @test */
    public function searchable_trait_has_required_methods()
    {
        $methods = ['scopeSearch', 'scopeSearchExact', 'getSearchableColumns'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(Searchable::class, $method),
                "Searchable should have {$method} method"
            );
        }
    }

    // ============================================
    // Merchant Domain Traits
    // ============================================

    /** @test */
    public function belongs_to_merchant_trait_exists()
    {
        $this->assertTrue(trait_exists(BelongsToMerchant::class));
    }

    /** @test */
    public function belongs_to_merchant_trait_has_required_methods()
    {
        $methods = ['merchant', 'scopeForMerchant', 'belongsToMerchant', 'authorizeForMerchant'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(BelongsToMerchant::class, $method),
                "BelongsToMerchant should have {$method} method"
            );
        }
    }

    /** @test */
    public function has_pricing_trait_exists()
    {
        $this->assertTrue(trait_exists(HasPricing::class));
    }

    /** @test */
    public function has_pricing_trait_has_required_methods()
    {
        $methods = ['getPrice', 'getPreviousPrice', 'hasDiscount', 'getDiscountAmount', 'getDiscountPercentage'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasPricing::class, $method),
                "HasPricing should have {$method} method"
            );
        }
    }

    /** @test */
    public function has_stock_trait_exists()
    {
        $this->assertTrue(trait_exists(HasStock::class));
    }

    /** @test */
    public function has_stock_trait_has_required_methods()
    {
        $methods = ['getStock', 'inStock', 'outOfStock', 'isLowStock', 'incrementStock', 'decrementStock'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasStock::class, $method),
                "HasStock should have {$method} method"
            );
        }
    }

    // ============================================
    // Catalog Domain Traits
    // ============================================

    /** @test */
    public function has_rating_trait_exists()
    {
        $this->assertTrue(trait_exists(HasRating::class));
    }

    /** @test */
    public function has_rating_trait_has_required_methods()
    {
        $methods = ['getRating', 'getRatingCount', 'hasRatings', 'addRating', 'getRatingStars'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasRating::class, $method),
                "HasRating should have {$method} method"
            );
        }
    }

    /** @test */
    public function has_images_trait_exists()
    {
        $this->assertTrue(trait_exists(HasImages::class));
    }

    /** @test */
    public function has_images_trait_has_required_methods()
    {
        $methods = ['getImages', 'getFirstImage', 'getThumbnail', 'getImageUrl', 'hasImages', 'addImage'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasImages::class, $method),
                "HasImages should have {$method} method"
            );
        }
    }

    // ============================================
    // Commerce Domain Traits
    // ============================================

    /** @test */
    public function has_cart_trait_exists()
    {
        $this->assertTrue(trait_exists(HasCart::class));
    }

    /** @test */
    public function has_cart_trait_has_required_methods()
    {
        $methods = ['getCartItems', 'getCartItemsCount', 'hasEmptyCart', 'addToCart', 'removeFromCart', 'clearCart'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasCart::class, $method),
                "HasCart should have {$method} method"
            );
        }
    }

    /** @test */
    public function has_purchases_trait_exists()
    {
        $this->assertTrue(trait_exists(HasPurchases::class));
    }

    /** @test */
    public function has_purchases_trait_has_required_methods()
    {
        $methods = ['purchases', 'activePurchases', 'completedPurchases', 'getTotalSpent', 'hasPurchases'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasPurchases::class, $method),
                "HasPurchases should have {$method} method"
            );
        }
    }

    // ============================================
    // Shipping Domain Traits
    // ============================================

    /** @test */
    public function has_address_trait_exists()
    {
        $this->assertTrue(trait_exists(HasAddress::class));
    }

    /** @test */
    public function has_address_trait_has_required_methods()
    {
        $methods = ['getAddress', 'getFullAddress', 'getCityName', 'hasCompleteAddress', 'setAddress'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasAddress::class, $method),
                "HasAddress should have {$method} method"
            );
        }
    }

    /** @test */
    public function trackable_trait_exists()
    {
        $this->assertTrue(trait_exists(Trackable::class));
    }

    /** @test */
    public function trackable_trait_has_required_methods()
    {
        $methods = ['getTrackingNumber', 'hasTrackingNumber', 'getTrackingHistory', 'isDelivered', 'getTrackingUrl'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(Trackable::class, $method),
                "Trackable should have {$method} method"
            );
        }
    }

    // ============================================
    // Identity Domain Traits
    // ============================================

    /** @test */
    public function has_roles_trait_exists()
    {
        $this->assertTrue(trait_exists(HasRoles::class));
    }

    /** @test */
    public function has_roles_trait_has_required_methods()
    {
        $methods = ['getRole', 'hasRole', 'hasAnyRole', 'isMerchant', 'isCustomer', 'assignRole'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasRoles::class, $method),
                "HasRoles should have {$method} method"
            );
        }
    }

    // ============================================
    // Accounting Domain Traits
    // ============================================

    /** @test */
    public function has_balance_trait_exists()
    {
        $this->assertTrue(trait_exists(HasBalance::class));
    }

    /** @test */
    public function has_balance_trait_has_required_methods()
    {
        $methods = ['getCurrentBalance', 'getPendingBalance', 'hasSufficientBalance', 'creditBalance', 'debitBalance'];

        foreach ($methods as $method) {
            $this->assertTrue(
                $this->traitHasMethod(HasBalance::class, $method),
                "HasBalance should have {$method} method"
            );
        }
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_traits_exist()
    {
        $traits = [
            HasSlug::class,
            HasStatus::class,
            HasTimestamps::class,
            Searchable::class,
            BelongsToMerchant::class,
            HasPricing::class,
            HasStock::class,
            HasRating::class,
            HasImages::class,
            HasCart::class,
            HasPurchases::class,
            HasAddress::class,
            Trackable::class,
            HasRoles::class,
            HasBalance::class,
        ];

        foreach ($traits as $trait) {
            $this->assertTrue(trait_exists($trait), "{$trait} should exist");
        }
    }

    /** @test */
    public function platform_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Platform\\Traits',
            HasSlug::class
        );
        $this->assertStringStartsWith(
            'App\\Domain\\Platform\\Traits',
            HasStatus::class
        );
    }

    /** @test */
    public function merchant_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\Traits',
            BelongsToMerchant::class
        );
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\Traits',
            HasPricing::class
        );
    }

    /** @test */
    public function catalog_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\Traits',
            HasRating::class
        );
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\Traits',
            HasImages::class
        );
    }

    /** @test */
    public function commerce_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Commerce\\Traits',
            HasCart::class
        );
        $this->assertStringStartsWith(
            'App\\Domain\\Commerce\\Traits',
            HasPurchases::class
        );
    }

    /** @test */
    public function shipping_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\Traits',
            HasAddress::class
        );
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\Traits',
            Trackable::class
        );
    }

    /** @test */
    public function identity_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Identity\\Traits',
            HasRoles::class
        );
    }

    /** @test */
    public function accounting_traits_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Accounting\\Traits',
            HasBalance::class
        );
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Check if a trait has a specific method
     */
    protected function traitHasMethod(string $trait, string $method): bool
    {
        $reflection = new \ReflectionClass($trait);
        return $reflection->hasMethod($method);
    }
}

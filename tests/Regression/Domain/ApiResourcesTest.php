<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use Illuminate\Http\Resources\Json\JsonResource;

// Catalog Resources
use App\Domain\Catalog\Resources\CatalogItemResource;
use App\Domain\Catalog\Resources\BrandResource;
use App\Domain\Catalog\Resources\CategoryResource;
use App\Domain\Catalog\Resources\CatalogReviewResource;

// Merchant Resources
use App\Domain\Merchant\Resources\MerchantItemResource;
use App\Domain\Merchant\Resources\MerchantResource;
use App\Domain\Merchant\Resources\MerchantBranchResource;
use App\Domain\Merchant\Resources\MerchantSettingResource;

// Commerce Resources
use App\Domain\Commerce\Resources\PurchaseResource;
use App\Domain\Commerce\Resources\MerchantPurchaseResource;
use App\Domain\Commerce\Resources\PurchaseTimelineResource;
use App\Domain\Commerce\Resources\CartResource;
use App\Domain\Commerce\Resources\CartItemResource;

// Shipping Resources
use App\Domain\Shipping\Resources\ShipmentResource;
use App\Domain\Shipping\Resources\CourierResource;
use App\Domain\Shipping\Resources\CityResource;
use App\Domain\Shipping\Resources\CountryResource;
use App\Domain\Shipping\Resources\ShippingOptionResource;

// Identity Resources
use App\Domain\Identity\Resources\UserResource;
use App\Domain\Identity\Resources\UserProfileResource;
use App\Domain\Identity\Resources\AddressResource;
use App\Domain\Identity\Resources\OperatorResource;
use App\Domain\Identity\Resources\OperatorRoleResource;

/**
 * Regression Tests for API Resources
 *
 * Phase 20: API Resources
 *
 * This test ensures that API resources are properly structured and functional.
 */
class ApiResourcesTest extends TestCase
{
    // =========================================================================
    // CATALOG RESOURCES
    // =========================================================================

    /** @test */
    public function catalog_item_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CatalogItemResource::class, JsonResource::class));
    }

    /** @test */
    public function catalog_item_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(CatalogItemResource::class, 'toArray'));
    }

    /** @test */
    public function catalog_item_resource_has_with_method()
    {
        $this->assertTrue(method_exists(CatalogItemResource::class, 'with'));
    }

    /** @test */
    public function brand_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(BrandResource::class, JsonResource::class));
    }

    /** @test */
    public function brand_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(BrandResource::class, 'toArray'));
    }

    /** @test */
    public function category_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CategoryResource::class, JsonResource::class));
    }

    /** @test */
    public function category_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(CategoryResource::class, 'toArray'));
    }

    /** @test */
    public function catalog_review_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CatalogReviewResource::class, JsonResource::class));
    }

    // =========================================================================
    // MERCHANT RESOURCES
    // =========================================================================

    /** @test */
    public function merchant_item_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(MerchantItemResource::class, JsonResource::class));
    }

    /** @test */
    public function merchant_item_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(MerchantItemResource::class, 'toArray'));
    }

    /** @test */
    public function merchant_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(MerchantResource::class, JsonResource::class));
    }

    /** @test */
    public function merchant_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(MerchantResource::class, 'toArray'));
    }

    /** @test */
    public function merchant_branch_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(MerchantBranchResource::class, JsonResource::class));
    }

    /** @test */
    public function merchant_setting_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(MerchantSettingResource::class, JsonResource::class));
    }

    // =========================================================================
    // COMMERCE RESOURCES
    // =========================================================================

    /** @test */
    public function purchase_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(PurchaseResource::class, JsonResource::class));
    }

    /** @test */
    public function purchase_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(PurchaseResource::class, 'toArray'));
    }

    /** @test */
    public function merchant_purchase_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(MerchantPurchaseResource::class, JsonResource::class));
    }

    /** @test */
    public function purchase_timeline_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(PurchaseTimelineResource::class, JsonResource::class));
    }

    /** @test */
    public function cart_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CartResource::class, JsonResource::class));
    }

    /** @test */
    public function cart_item_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CartItemResource::class, JsonResource::class));
    }

    // =========================================================================
    // SHIPPING RESOURCES
    // =========================================================================

    /** @test */
    public function shipment_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(ShipmentResource::class, JsonResource::class));
    }

    /** @test */
    public function shipment_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(ShipmentResource::class, 'toArray'));
    }

    /** @test */
    public function courier_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CourierResource::class, JsonResource::class));
    }

    /** @test */
    public function city_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CityResource::class, JsonResource::class));
    }

    /** @test */
    public function country_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(CountryResource::class, JsonResource::class));
    }

    /** @test */
    public function shipping_option_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(ShippingOptionResource::class, JsonResource::class));
    }

    // =========================================================================
    // IDENTITY RESOURCES
    // =========================================================================

    /** @test */
    public function user_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(UserResource::class, JsonResource::class));
    }

    /** @test */
    public function user_resource_has_to_array_method()
    {
        $this->assertTrue(method_exists(UserResource::class, 'toArray'));
    }

    /** @test */
    public function user_profile_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(UserProfileResource::class, JsonResource::class));
    }

    /** @test */
    public function address_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(AddressResource::class, JsonResource::class));
    }

    /** @test */
    public function operator_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(OperatorResource::class, JsonResource::class));
    }

    /** @test */
    public function operator_role_resource_extends_json_resource()
    {
        $this->assertTrue(is_subclass_of(OperatorRoleResource::class, JsonResource::class));
    }

    // =========================================================================
    // COMMON FEATURES
    // =========================================================================

    /** @test */
    public function all_resources_extend_json_resource()
    {
        $resources = [
            CatalogItemResource::class,
            BrandResource::class,
            CategoryResource::class,
            CatalogReviewResource::class,
            MerchantItemResource::class,
            MerchantResource::class,
            MerchantBranchResource::class,
            MerchantSettingResource::class,
            PurchaseResource::class,
            MerchantPurchaseResource::class,
            PurchaseTimelineResource::class,
            CartResource::class,
            CartItemResource::class,
            ShipmentResource::class,
            CourierResource::class,
            CityResource::class,
            CountryResource::class,
            ShippingOptionResource::class,
            UserResource::class,
            UserProfileResource::class,
            AddressResource::class,
            OperatorResource::class,
            OperatorRoleResource::class,
        ];

        foreach ($resources as $resourceClass) {
            $this->assertTrue(
                is_subclass_of($resourceClass, JsonResource::class),
                "{$resourceClass} should extend JsonResource"
            );
        }
    }

    /** @test */
    public function all_resources_have_to_array_method()
    {
        $resources = [
            CatalogItemResource::class,
            BrandResource::class,
            CategoryResource::class,
            CatalogReviewResource::class,
            MerchantItemResource::class,
            MerchantResource::class,
            MerchantBranchResource::class,
            MerchantSettingResource::class,
            PurchaseResource::class,
            MerchantPurchaseResource::class,
            PurchaseTimelineResource::class,
            CartResource::class,
            CartItemResource::class,
            ShipmentResource::class,
            CourierResource::class,
            CityResource::class,
            CountryResource::class,
            ShippingOptionResource::class,
            UserResource::class,
            UserProfileResource::class,
            AddressResource::class,
            OperatorResource::class,
            OperatorRoleResource::class,
        ];

        foreach ($resources as $resourceClass) {
            $this->assertTrue(
                method_exists($resourceClass, 'toArray'),
                "{$resourceClass} should have toArray() method"
            );
        }
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function catalog_resources_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Resources'));
    }

    /** @test */
    public function merchant_resources_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Resources'));
    }

    /** @test */
    public function commerce_resources_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Resources'));
    }

    /** @test */
    public function shipping_resources_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Resources'));
    }

    /** @test */
    public function identity_resources_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Resources'));
    }
}

<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Catalog DTOs
use App\Domain\Catalog\DTOs\CatalogItemCardDTO;
use App\DataTransferObjects\CatalogItemCardDTO as LegacyCatalogItemCardDTO;

// Commerce DTOs
use App\Domain\Commerce\DTOs\CartItemDTO;
use App\Domain\Commerce\DTOs\CartTotalsDTO;
use App\Domain\Commerce\DTOs\BranchCartDTO;
use App\Domain\Commerce\DTOs\CheckoutAddressDTO;

// Shipping DTOs
use App\Domain\Shipping\DTOs\ShippingOptionDTO;

// Platform DTOs
use App\Domain\Platform\DTOs\MonetaryValueDTO;

/**
 * Regression Tests for Domain DTOs
 *
 * Phase 10: Data Transfer Objects
 *
 * This test ensures that DTOs are properly structured and functional.
 */
class DTOsTest extends TestCase
{
    // =========================================================================
    // CATALOG DTOs
    // =========================================================================

    /** @test */
    public function catalog_item_card_dto_exists()
    {
        $this->assertTrue(class_exists(CatalogItemCardDTO::class));
    }

    /** @test */
    public function legacy_catalog_item_card_dto_extends_domain_dto()
    {
        $this->assertTrue(is_subclass_of(LegacyCatalogItemCardDTO::class, CatalogItemCardDTO::class));
    }

    /** @test */
    public function catalog_item_card_dto_has_required_properties()
    {
        $reflection = new \ReflectionClass(CatalogItemCardDTO::class);
        $properties = $reflection->getProperties(\ReflectionProperty::IS_PUBLIC);
        $propertyNames = array_map(fn($p) => $p->getName(), $properties);

        $requiredProperties = [
            'catalogItemId',
            'catalogItemName',
            'catalogItemSlug',
            'price',
            'priceFormatted',
            'stock',
            'inStock',
            'hasMerchant',
            'offersCount',
        ];

        foreach ($requiredProperties as $prop) {
            $this->assertContains($prop, $propertyNames, "Property '{$prop}' missing from CatalogItemCardDTO");
        }
    }

    /** @test */
    public function catalog_item_card_dto_has_static_factory_methods()
    {
        $this->assertTrue(method_exists(CatalogItemCardDTO::class, 'fromMerchantItem'));
        $this->assertTrue(method_exists(CatalogItemCardDTO::class, 'fromCatalogItem'));
        $this->assertTrue(method_exists(CatalogItemCardDTO::class, 'fromCatalogItemFirst'));
    }

    // =========================================================================
    // COMMERCE DTOs
    // =========================================================================

    /** @test */
    public function cart_item_dto_exists()
    {
        $this->assertTrue(class_exists(CartItemDTO::class));
    }

    /** @test */
    public function cart_item_dto_can_be_created_from_array()
    {
        $data = [
            'key' => 'test_key',
            'merchant_item_id' => 123,
            'merchant_id' => 456,
            'branch_id' => 789,
            'branch_name' => 'Test Branch',
            'catalog_item_id' => 111,
            'name' => 'Test Item',
            'name_ar' => 'صنف اختبار',
            'unit_price' => 100.00,
            'effective_price' => 100.00,
            'total_price' => 200.00,
            'qty' => 2,
            'stock' => 10,
            'weight' => 1.5,
        ];

        $dto = CartItemDTO::fromArray($data);

        $this->assertEquals('test_key', $dto->key);
        $this->assertEquals(123, $dto->merchantItemId);
        $this->assertEquals(456, $dto->merchantId);
        $this->assertEquals(789, $dto->branchId);
        $this->assertEquals('Test Branch', $dto->branchName);
        $this->assertEquals('Test Item', $dto->name);
        $this->assertEquals(100.00, $dto->unitPrice);
        $this->assertEquals(2, $dto->qty);
        $this->assertEquals(1.5, $dto->weight);
    }

    /** @test */
    public function cart_item_dto_can_be_converted_to_array()
    {
        $data = [
            'key' => 'test_key',
            'merchant_item_id' => 123,
            'merchant_id' => 456,
            'branch_id' => 789,
            'qty' => 2,
            'unit_price' => 100.00,
        ];

        $dto = CartItemDTO::fromArray($data);
        $array = $dto->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test_key', $array['key']);
        $this->assertEquals(123, $array['merchant_item_id']);
    }

    /** @test */
    public function cart_item_dto_has_helper_methods()
    {
        $this->assertTrue(method_exists(CartItemDTO::class, 'getLocalizedName'));
        $this->assertTrue(method_exists(CartItemDTO::class, 'getLocalizedBrandName'));
        $this->assertTrue(method_exists(CartItemDTO::class, 'isInStock'));
        $this->assertTrue(method_exists(CartItemDTO::class, 'hasWholesale'));
    }

    /** @test */
    public function cart_totals_dto_exists()
    {
        $this->assertTrue(class_exists(CartTotalsDTO::class));
    }

    /** @test */
    public function cart_totals_dto_can_be_created_from_array()
    {
        $data = [
            'qty' => 5,
            'subtotal' => 500.00,
            'discount' => 50.00,
            'total' => 450.00,
        ];

        $dto = CartTotalsDTO::fromArray($data);

        $this->assertEquals(5, $dto->qty);
        $this->assertEquals(500.00, $dto->subtotal);
        $this->assertEquals(50.00, $dto->discount);
        $this->assertEquals(450.00, $dto->total);
    }

    /** @test */
    public function cart_totals_dto_has_helper_methods()
    {
        $this->assertTrue(method_exists(CartTotalsDTO::class, 'hasDiscount'));
        $this->assertTrue(method_exists(CartTotalsDTO::class, 'isEmpty'));
        $this->assertTrue(method_exists(CartTotalsDTO::class, 'getFormattedTotal'));
    }

    /** @test */
    public function branch_cart_dto_exists()
    {
        $this->assertTrue(class_exists(BranchCartDTO::class));
    }

    /** @test */
    public function branch_cart_dto_can_be_created_from_array()
    {
        $data = [
            'branch_id' => 123,
            'branch_name' => 'Test Branch',
            'merchant_id' => 456,
            'merchant_name' => 'Test Merchant',
            'items' => [
                'key1' => [
                    'key' => 'key1',
                    'merchant_item_id' => 1,
                    'qty' => 2,
                ],
            ],
            'totals' => [
                'qty' => 2,
                'total' => 200.00,
            ],
            'has_other_branches' => true,
        ];

        $dto = BranchCartDTO::fromArray($data);

        $this->assertEquals(123, $dto->branchId);
        $this->assertEquals('Test Branch', $dto->branchName);
        $this->assertEquals(456, $dto->merchantId);
        $this->assertCount(1, $dto->items);
        $this->assertTrue($dto->hasOtherBranches);
    }

    /** @test */
    public function branch_cart_dto_has_helper_methods()
    {
        $this->assertTrue(method_exists(BranchCartDTO::class, 'getItemCount'));
        $this->assertTrue(method_exists(BranchCartDTO::class, 'isEmpty'));
        $this->assertTrue(method_exists(BranchCartDTO::class, 'getTotalQty'));
        $this->assertTrue(method_exists(BranchCartDTO::class, 'getTotalWeight'));
    }

    /** @test */
    public function checkout_address_dto_exists()
    {
        $this->assertTrue(class_exists(CheckoutAddressDTO::class));
    }

    /** @test */
    public function checkout_address_dto_can_be_created_from_array()
    {
        $data = [
            'customer_name' => 'John Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+1234567890',
            'customer_address' => '123 Main St',
            'country_id' => 1,
            'customer_country' => 'Saudi Arabia',
            'city_id' => 10,
            'customer_city' => 'Riyadh',
            'lat' => 24.7136,
            'lng' => 46.6753,
        ];

        $dto = CheckoutAddressDTO::fromArray($data);

        $this->assertEquals('John Doe', $dto->customerName);
        $this->assertEquals('john@example.com', $dto->customerEmail);
        $this->assertEquals('+1234567890', $dto->customerPhone);
        $this->assertEquals('123 Main St', $dto->customerAddress);
        $this->assertEquals(1, $dto->countryId);
        $this->assertTrue($dto->hasCoordinates());
    }

    /** @test */
    public function checkout_address_dto_has_helper_methods()
    {
        $this->assertTrue(method_exists(CheckoutAddressDTO::class, 'hasCoordinates'));
        $this->assertTrue(method_exists(CheckoutAddressDTO::class, 'getFullAddress'));
        $this->assertTrue(method_exists(CheckoutAddressDTO::class, 'isValid'));
    }

    // =========================================================================
    // SHIPPING DTOs
    // =========================================================================

    /** @test */
    public function shipping_option_dto_exists()
    {
        $this->assertTrue(class_exists(ShippingOptionDTO::class));
    }

    /** @test */
    public function shipping_option_dto_can_be_created_from_array()
    {
        $data = [
            'id' => 1,
            'name' => 'Standard Shipping',
            'name_ar' => 'الشحن العادي',
            'provider' => 'manual',
            'price' => 25.00,
            'estimated_days' => '3-5 days',
            'is_free' => false,
        ];

        $dto = ShippingOptionDTO::fromArray($data);

        $this->assertEquals(1, $dto->id);
        $this->assertEquals('Standard Shipping', $dto->name);
        $this->assertEquals('manual', $dto->provider);
        $this->assertEquals(25.00, $dto->price);
        $this->assertFalse($dto->isFree);
    }

    /** @test */
    public function shipping_option_dto_has_helper_methods()
    {
        $this->assertTrue(method_exists(ShippingOptionDTO::class, 'getLocalizedName'));
        $this->assertTrue(method_exists(ShippingOptionDTO::class, 'getDisplayPrice'));
    }

    // =========================================================================
    // PLATFORM DTOs
    // =========================================================================

    /** @test */
    public function monetary_value_dto_exists()
    {
        $this->assertTrue(class_exists(MonetaryValueDTO::class));
    }

    /** @test */
    public function monetary_value_dto_can_be_created_from_array()
    {
        $data = [
            'amount' => 100.00,
            'formatted' => 'SAR 100.00',
            'code' => 'SAR',
            'sign' => 'SAR',
        ];

        $dto = MonetaryValueDTO::fromArray($data);

        $this->assertEquals(100.00, $dto->amount);
        $this->assertEquals('SAR 100.00', $dto->formatted);
        $this->assertEquals('SAR', $dto->code);
    }

    /** @test */
    public function monetary_value_dto_has_helper_methods()
    {
        $this->assertTrue(method_exists(MonetaryValueDTO::class, 'isZero'));
        $this->assertTrue(method_exists(MonetaryValueDTO::class, 'isPositive'));
        $this->assertTrue(method_exists(MonetaryValueDTO::class, 'fromAmount'));
    }

    /** @test */
    public function monetary_value_dto_can_convert_to_string()
    {
        $data = [
            'amount' => 100.00,
            'formatted' => 'SAR 100.00',
            'code' => 'SAR',
            'sign' => 'SAR',
        ];

        $dto = MonetaryValueDTO::fromArray($data);

        $this->assertEquals('SAR 100.00', (string) $dto);
    }

    // =========================================================================
    // DTO DIRECTORIES EXIST
    // =========================================================================

    /** @test */
    public function catalog_dtos_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/DTOs'));
    }

    /** @test */
    public function commerce_dtos_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/DTOs'));
    }

    /** @test */
    public function shipping_dtos_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/DTOs'));
    }

    /** @test */
    public function platform_dtos_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Platform/DTOs'));
    }
}

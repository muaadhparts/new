<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Base Exception
use App\Domain\Platform\Exceptions\DomainException;
use App\Domain\Platform\Exceptions\ValidationException;
use App\Domain\Platform\Exceptions\ConfigurationException;

// Commerce Exceptions
use App\Domain\Commerce\Exceptions\InsufficientStockException;
use App\Domain\Commerce\Exceptions\CartException;
use App\Domain\Commerce\Exceptions\CheckoutException;
use App\Domain\Commerce\Exceptions\PaymentException;

// Merchant Exceptions
use App\Domain\Merchant\Exceptions\MerchantNotFoundException;
use App\Domain\Merchant\Exceptions\MerchantInactiveException;
use App\Domain\Merchant\Exceptions\InvalidPriceException;

// Catalog Exceptions
use App\Domain\Catalog\Exceptions\CatalogItemNotFoundException;
use App\Domain\Catalog\Exceptions\InvalidPartNumberException;
use App\Domain\Catalog\Exceptions\CategoryNotFoundException;

// Shipping Exceptions
use App\Domain\Shipping\Exceptions\ShippingUnavailableException;
use App\Domain\Shipping\Exceptions\InvalidAddressException;
use App\Domain\Shipping\Exceptions\ShipmentException;

// Identity Exceptions
use App\Domain\Identity\Exceptions\AuthenticationException;
use App\Domain\Identity\Exceptions\UnauthorizedException;
use App\Domain\Identity\Exceptions\UserNotFoundException;

/**
 * Regression Tests for Domain Exceptions
 *
 * Phase 15: Domain Exceptions
 *
 * This test ensures that domain exceptions are properly structured and functional.
 */
class DomainExceptionsTest extends TestCase
{
    // =========================================================================
    // VALIDATION EXCEPTION
    // =========================================================================

    /** @test */
    public function validation_exception_can_be_created()
    {
        $exception = new ValidationException('Validation failed', ['email' => ['Invalid email']]);

        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals(422, $exception->getCode());
        $this->assertEquals(['email' => ['Invalid email']], $exception->getErrors());
    }

    /** @test */
    public function validation_exception_with_errors_factory()
    {
        $exception = ValidationException::withErrors([
            'name' => ['Name is required'],
            'email' => ['Invalid email format'],
        ]);

        $this->assertCount(2, $exception->getErrors());
        $this->assertEquals('Platform', $exception->getDomain());
    }

    /** @test */
    public function validation_exception_for_field_factory()
    {
        $exception = ValidationException::forField('email', 'Email is required');

        $this->assertArrayHasKey('email', $exception->getErrors());
    }

    // =========================================================================
    // CONFIGURATION EXCEPTION
    // =========================================================================

    /** @test */
    public function configuration_exception_missing_factory()
    {
        $exception = ConfigurationException::missing('app.key');

        $this->assertStringContainsString('app.key', $exception->getMessage());
        $this->assertEquals(500, $exception->getCode());
    }

    /** @test */
    public function configuration_exception_invalid_factory()
    {
        $exception = ConfigurationException::invalid('cache.driver', 'Unknown driver');

        $this->assertStringContainsString('cache.driver', $exception->getMessage());
        $this->assertStringContainsString('Unknown driver', $exception->getMessage());
    }

    // =========================================================================
    // INSUFFICIENT STOCK EXCEPTION
    // =========================================================================

    /** @test */
    public function insufficient_stock_exception_can_be_created()
    {
        $exception = new InsufficientStockException(
            merchantItemId: 1,
            requestedQuantity: 10,
            availableStock: 5,
            itemName: 'Brake Pad'
        );

        $this->assertEquals(1, $exception->merchantItemId);
        $this->assertEquals(10, $exception->requestedQuantity);
        $this->assertEquals(5, $exception->availableStock);
        $this->assertEquals(5, $exception->getShortage());
        $this->assertEquals('Commerce', $exception->getDomain());
    }

    /** @test */
    public function insufficient_stock_exception_for_item_factory()
    {
        $exception = InsufficientStockException::forItem(1, 10, 3, 'Oil Filter');

        $this->assertEquals(7, $exception->getShortage());
        $this->assertStringContainsString('Oil Filter', $exception->getMessage());
    }

    // =========================================================================
    // CART EXCEPTION
    // =========================================================================

    /** @test */
    public function cart_exception_empty_factory()
    {
        $exception = CartException::empty();

        $this->assertStringContainsString('empty', $exception->getMessage());
        $this->assertEquals('Commerce', $exception->getDomain());
    }

    /** @test */
    public function cart_exception_item_not_found_factory()
    {
        $exception = CartException::itemNotFound(123);

        $this->assertEquals(404, $exception->getCode());
        $this->assertArrayHasKey('merchant_item_id', $exception->getContext());
    }

    /** @test */
    public function cart_exception_invalid_quantity_factory()
    {
        $exception = CartException::invalidQuantity(0, 1);

        $this->assertStringContainsString('at least 1', $exception->getMessage());
    }

    /** @test */
    public function cart_exception_item_unavailable_factory()
    {
        $exception = CartException::itemUnavailable(123, 'out_of_stock');

        $this->assertStringContainsString('no longer available', $exception->getMessage());
    }

    // =========================================================================
    // CHECKOUT EXCEPTION
    // =========================================================================

    /** @test */
    public function checkout_exception_no_shipping_address_factory()
    {
        $exception = CheckoutException::noShippingAddress();

        $this->assertStringContainsString('address', $exception->getMessage());
        $this->assertEquals('Commerce', $exception->getDomain());
    }

    /** @test */
    public function checkout_exception_minimum_not_met_factory()
    {
        $exception = CheckoutException::minimumNotMet(100, 50, 'SAR');

        $this->assertStringContainsString('100', $exception->getMessage());
        $this->assertStringContainsString('50', $exception->getMessage());
    }

    /** @test */
    public function checkout_exception_already_completed_factory()
    {
        $exception = CheckoutException::alreadyCompleted(123);

        $this->assertArrayHasKey('purchase_id', $exception->getContext());
    }

    // =========================================================================
    // PAYMENT EXCEPTION
    // =========================================================================

    /** @test */
    public function payment_exception_declined_factory()
    {
        $exception = PaymentException::declined('insufficient_funds');

        $this->assertStringContainsString('declined', $exception->getMessage());
        $this->assertEquals('Commerce', $exception->getDomain());
    }

    /** @test */
    public function payment_exception_gateway_error_factory()
    {
        $exception = PaymentException::gatewayError('stripe', 'Connection timeout');

        $this->assertEquals(502, $exception->getCode());
        $this->assertArrayHasKey('gateway', $exception->getContext());
    }

    /** @test */
    public function payment_exception_amount_mismatch_factory()
    {
        $exception = PaymentException::amountMismatch(100.00, 99.00);

        $this->assertStringContainsString('100', $exception->getMessage());
        $this->assertStringContainsString('99', $exception->getMessage());
    }

    // =========================================================================
    // MERCHANT NOT FOUND EXCEPTION
    // =========================================================================

    /** @test */
    public function merchant_not_found_exception_with_id_factory()
    {
        $exception = MerchantNotFoundException::withId(123);

        $this->assertEquals(123, $exception->merchantIdentifier);
        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals('Merchant', $exception->getDomain());
    }

    /** @test */
    public function merchant_not_found_exception_with_slug_factory()
    {
        $exception = MerchantNotFoundException::withSlug('test-shop');

        $this->assertEquals('test-shop', $exception->merchantIdentifier);
        $this->assertStringContainsString('slug', $exception->getMessage());
    }

    // =========================================================================
    // MERCHANT INACTIVE EXCEPTION
    // =========================================================================

    /** @test */
    public function merchant_inactive_exception_suspended_factory()
    {
        $exception = MerchantInactiveException::suspended(123);

        $this->assertEquals('suspended', $exception->status);
        $this->assertEquals(403, $exception->getCode());
    }

    /** @test */
    public function merchant_inactive_exception_pending_factory()
    {
        $exception = MerchantInactiveException::pending(123);

        $this->assertStringContainsString('pending', $exception->status);
    }

    // =========================================================================
    // INVALID PRICE EXCEPTION
    // =========================================================================

    /** @test */
    public function invalid_price_exception_negative_factory()
    {
        $exception = InvalidPriceException::negative(-10.00);

        $this->assertEquals(-10.00, $exception->price);
        $this->assertStringContainsString('negative', $exception->getMessage());
        $this->assertEquals('Merchant', $exception->getDomain());
    }

    /** @test */
    public function invalid_price_exception_below_cost_factory()
    {
        $exception = InvalidPriceException::belowCost(50.00, 60.00);

        $this->assertStringContainsString('cost', $exception->getMessage());
    }

    // =========================================================================
    // CATALOG ITEM NOT FOUND EXCEPTION
    // =========================================================================

    /** @test */
    public function catalog_item_not_found_exception_with_id_factory()
    {
        $exception = CatalogItemNotFoundException::withId(123);

        $this->assertEquals(123, $exception->itemIdentifier);
        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals('Catalog', $exception->getDomain());
    }

    /** @test */
    public function catalog_item_not_found_exception_with_sku_factory()
    {
        $exception = CatalogItemNotFoundException::withSku('SKU-123');

        $this->assertEquals('SKU-123', $exception->itemIdentifier);
    }

    /** @test */
    public function catalog_item_not_found_exception_with_part_number_factory()
    {
        $exception = CatalogItemNotFoundException::withPartNumber('04465-33450');

        $this->assertStringContainsString('part_number', $exception->getMessage());
    }

    // =========================================================================
    // INVALID PART NUMBER EXCEPTION
    // =========================================================================

    /** @test */
    public function invalid_part_number_exception_empty_factory()
    {
        $exception = InvalidPartNumberException::empty();

        $this->assertStringContainsString('empty', $exception->getMessage());
        $this->assertEquals('Catalog', $exception->getDomain());
    }

    /** @test */
    public function invalid_part_number_exception_too_short_factory()
    {
        $exception = InvalidPartNumberException::tooShort('AB', 3);

        $this->assertEquals('AB', $exception->partNumber);
        $this->assertStringContainsString('3', $exception->getMessage());
    }

    // =========================================================================
    // CATEGORY NOT FOUND EXCEPTION
    // =========================================================================

    /** @test */
    public function category_not_found_exception_with_id_factory()
    {
        $exception = CategoryNotFoundException::withId(123);

        $this->assertEquals(123, $exception->categoryIdentifier);
        $this->assertEquals('Catalog', $exception->getDomain());
    }

    /** @test */
    public function category_not_found_exception_with_slug_factory()
    {
        $exception = CategoryNotFoundException::withSlug('brake-pads');

        $this->assertEquals('brake-pads', $exception->categoryIdentifier);
    }

    // =========================================================================
    // SHIPPING UNAVAILABLE EXCEPTION
    // =========================================================================

    /** @test */
    public function shipping_unavailable_exception_for_location_factory()
    {
        $exception = ShippingUnavailableException::forLocation('Riyadh', 'Saudi Arabia');

        $this->assertStringContainsString('Riyadh', $exception->getMessage());
        $this->assertEquals('Shipping', $exception->getDomain());
    }

    /** @test */
    public function shipping_unavailable_exception_no_carriers_factory()
    {
        $exception = ShippingUnavailableException::noCarriers();

        $this->assertStringContainsString('carriers', $exception->getMessage());
    }

    /** @test */
    public function shipping_unavailable_exception_merchant_restriction_factory()
    {
        $exception = ShippingUnavailableException::merchantDoesNotShip(123, 'Jeddah');

        $this->assertArrayHasKey('merchant_id', $exception->getContext());
    }

    // =========================================================================
    // INVALID ADDRESS EXCEPTION
    // =========================================================================

    /** @test */
    public function invalid_address_exception_missing_fields_factory()
    {
        $exception = InvalidAddressException::missingFields(['street', 'city']);

        $this->assertCount(2, $exception->getFieldErrors());
        $this->assertEquals('Shipping', $exception->getDomain());
    }

    /** @test */
    public function invalid_address_exception_invalid_city_factory()
    {
        $exception = InvalidAddressException::invalidCity('UnknownCity');

        $this->assertArrayHasKey('city', $exception->getFieldErrors());
    }

    // =========================================================================
    // SHIPMENT EXCEPTION
    // =========================================================================

    /** @test */
    public function shipment_exception_not_found_factory()
    {
        $exception = ShipmentException::notFound(123);

        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals('Shipping', $exception->getDomain());
    }

    /** @test */
    public function shipment_exception_cannot_cancel_factory()
    {
        $exception = ShipmentException::cannotCancel(123, 'delivered');

        $this->assertStringContainsString('delivered', $exception->getMessage());
    }

    /** @test */
    public function shipment_exception_invalid_transition_factory()
    {
        $exception = ShipmentException::invalidTransition('pending', 'delivered');

        $this->assertStringContainsString('pending', $exception->getMessage());
        $this->assertStringContainsString('delivered', $exception->getMessage());
    }

    // =========================================================================
    // AUTHENTICATION EXCEPTION
    // =========================================================================

    /** @test */
    public function authentication_exception_invalid_credentials_factory()
    {
        $exception = AuthenticationException::invalidCredentials();

        $this->assertEquals(401, $exception->getCode());
        $this->assertEquals('Identity', $exception->getDomain());
    }

    /** @test */
    public function authentication_exception_not_verified_factory()
    {
        $exception = AuthenticationException::notVerified(123);

        $this->assertEquals(403, $exception->getCode());
        $this->assertStringContainsString('verify', $exception->getMessage());
    }

    /** @test */
    public function authentication_exception_account_locked_factory()
    {
        $exception = AuthenticationException::accountLocked(123, 15);

        $this->assertStringContainsString('15 minutes', $exception->getMessage());
    }

    /** @test */
    public function authentication_exception_token_expired_factory()
    {
        $exception = AuthenticationException::tokenExpired();

        $this->assertStringContainsString('expired', $exception->getMessage());
    }

    // =========================================================================
    // UNAUTHORIZED EXCEPTION
    // =========================================================================

    /** @test */
    public function unauthorized_exception_missing_permission_factory()
    {
        $exception = UnauthorizedException::missingPermission('manage_users');

        $this->assertEquals(403, $exception->getCode());
        $this->assertStringContainsString('manage_users', $exception->getMessage());
        $this->assertEquals('Identity', $exception->getDomain());
    }

    /** @test */
    public function unauthorized_exception_for_resource_factory()
    {
        $exception = UnauthorizedException::forResource('order', 123);

        $this->assertArrayHasKey('resource_id', $exception->getContext());
    }

    /** @test */
    public function unauthorized_exception_merchant_only_factory()
    {
        $exception = UnauthorizedException::merchantOnly();

        $this->assertStringContainsString('merchants', $exception->getMessage());
    }

    // =========================================================================
    // USER NOT FOUND EXCEPTION
    // =========================================================================

    /** @test */
    public function user_not_found_exception_with_id_factory()
    {
        $exception = UserNotFoundException::withId(123);

        $this->assertEquals(123, $exception->userIdentifier);
        $this->assertEquals(404, $exception->getCode());
        $this->assertEquals('Identity', $exception->getDomain());
    }

    /** @test */
    public function user_not_found_exception_with_email_factory()
    {
        $exception = UserNotFoundException::withEmail('test@example.com');

        $this->assertEquals('test@example.com', $exception->userIdentifier);
    }

    // =========================================================================
    // DOMAIN EXCEPTION BASE CLASS
    // =========================================================================

    /** @test */
    public function domain_exception_to_array()
    {
        $exception = ValidationException::forField('email', 'Invalid');

        $array = $exception->toArray();

        $this->assertArrayHasKey('error_code', $array);
        $this->assertArrayHasKey('message', $array);
        $this->assertArrayHasKey('domain', $array);
        $this->assertArrayHasKey('context', $array);
    }

    /** @test */
    public function domain_exception_should_report()
    {
        $validation = ValidationException::forField('email', 'Invalid');
        $config = ConfigurationException::missing('key');

        $this->assertFalse($validation->shouldReport()); // Expected error
        $this->assertTrue($config->shouldReport()); // Unexpected error
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function platform_exceptions_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Platform/Exceptions'));
    }

    /** @test */
    public function commerce_exceptions_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Exceptions'));
    }

    /** @test */
    public function merchant_exceptions_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Exceptions'));
    }

    /** @test */
    public function catalog_exceptions_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Exceptions'));
    }

    /** @test */
    public function shipping_exceptions_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Exceptions'));
    }

    /** @test */
    public function identity_exceptions_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Exceptions'));
    }
}

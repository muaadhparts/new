<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use Illuminate\Foundation\Http\FormRequest;

// Commerce Requests
use App\Domain\Commerce\Requests\AddToCartRequest;
use App\Domain\Commerce\Requests\UpdateCartItemRequest;
use App\Domain\Commerce\Requests\CheckoutRequest;

// Merchant Requests
use App\Domain\Merchant\Requests\UpdateStockRequest;
use App\Domain\Merchant\Requests\UpdatePriceRequest;
use App\Domain\Merchant\Requests\StoreBranchRequest;

// Catalog Requests
use App\Domain\Catalog\Requests\StoreReviewRequest;
use App\Domain\Catalog\Requests\SearchRequest;

// Shipping Requests
use App\Domain\Shipping\Requests\StoreAddressRequest;
use App\Domain\Shipping\Requests\CalculateShippingRequest;

// Identity Requests
use App\Domain\Identity\Requests\UpdateProfileRequest;
use App\Domain\Identity\Requests\ChangePasswordRequest;
use App\Domain\Identity\Requests\RegisterRequest;

/**
 * Regression Tests for Form Requests
 *
 * Phase 18: Form Requests
 *
 * This test ensures that form requests are properly structured and functional.
 */
class FormRequestsTest extends TestCase
{
    // =========================================================================
    // COMMERCE REQUESTS
    // =========================================================================

    /** @test */
    public function add_to_cart_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(AddToCartRequest::class, FormRequest::class));
    }

    /** @test */
    public function add_to_cart_request_has_required_methods()
    {
        $request = new AddToCartRequest();

        $this->assertTrue(method_exists($request, 'authorize'));
        $this->assertTrue(method_exists($request, 'rules'));
        $this->assertTrue(method_exists($request, 'messages'));
    }

    /** @test */
    public function add_to_cart_request_has_validation_rules()
    {
        $request = new AddToCartRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('merchant_item_id', $rules);
        $this->assertArrayHasKey('quantity', $rules);
    }

    /** @test */
    public function update_cart_item_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(UpdateCartItemRequest::class, FormRequest::class));
    }

    /** @test */
    public function update_cart_item_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(UpdateCartItemRequest::class, 'isRemoval'));
    }

    /** @test */
    public function checkout_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(CheckoutRequest::class, FormRequest::class));
    }

    /** @test */
    public function checkout_request_has_validation_rules()
    {
        $request = new CheckoutRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('shipping_address', $rules);
        $this->assertArrayHasKey('payment_method', $rules);
    }

    /** @test */
    public function checkout_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(CheckoutRequest::class, 'getShippingAddress'));
        $this->assertTrue(method_exists(CheckoutRequest::class, 'shouldSaveAddress'));
    }

    // =========================================================================
    // MERCHANT REQUESTS
    // =========================================================================

    /** @test */
    public function update_stock_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(UpdateStockRequest::class, FormRequest::class));
    }

    /** @test */
    public function update_stock_request_has_validation_rules()
    {
        $request = new UpdateStockRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('stock', $rules);
        $this->assertArrayHasKey('reason', $rules);
    }

    /** @test */
    public function update_stock_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(UpdateStockRequest::class, 'getAdjustmentType'));
        $this->assertTrue(method_exists(UpdateStockRequest::class, 'getReason'));
    }

    /** @test */
    public function update_price_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(UpdatePriceRequest::class, FormRequest::class));
    }

    /** @test */
    public function update_price_request_has_validation_rules()
    {
        $request = new UpdatePriceRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('price', $rules);
        $this->assertArrayHasKey('discount', $rules);
    }

    /** @test */
    public function update_price_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(UpdatePriceRequest::class, 'hasDiscount'));
        $this->assertTrue(method_exists(UpdatePriceRequest::class, 'getDiscountedPrice'));
    }

    /** @test */
    public function store_branch_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(StoreBranchRequest::class, FormRequest::class));
    }

    /** @test */
    public function store_branch_request_has_validation_rules()
    {
        $request = new StoreBranchRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('address', $rules);
        $this->assertArrayHasKey('city_id', $rules);
    }

    /** @test */
    public function store_branch_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(StoreBranchRequest::class, 'isMain'));
        $this->assertTrue(method_exists(StoreBranchRequest::class, 'getCoordinates'));
    }

    // =========================================================================
    // CATALOG REQUESTS
    // =========================================================================

    /** @test */
    public function store_review_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(StoreReviewRequest::class, FormRequest::class));
    }

    /** @test */
    public function store_review_request_has_validation_rules()
    {
        $request = new StoreReviewRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('rating', $rules);
        $this->assertArrayHasKey('comment', $rules);
    }

    /** @test */
    public function store_review_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(StoreReviewRequest::class, 'isPositive'));
        $this->assertTrue(method_exists(StoreReviewRequest::class, 'hasImages'));
    }

    /** @test */
    public function search_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(SearchRequest::class, FormRequest::class));
    }

    /** @test */
    public function search_request_has_validation_rules()
    {
        $request = new SearchRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('q', $rules);
        $this->assertArrayHasKey('category_id', $rules);
        $this->assertArrayHasKey('sort', $rules);
    }

    /** @test */
    public function search_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(SearchRequest::class, 'getSearchQuery'));
        $this->assertTrue(method_exists(SearchRequest::class, 'getSortOption'));
        $this->assertTrue(method_exists(SearchRequest::class, 'getPerPage'));
        $this->assertTrue(method_exists(SearchRequest::class, 'getPriceRange'));
    }

    // =========================================================================
    // SHIPPING REQUESTS
    // =========================================================================

    /** @test */
    public function store_address_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(StoreAddressRequest::class, FormRequest::class));
    }

    /** @test */
    public function store_address_request_has_validation_rules()
    {
        $request = new StoreAddressRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('phone', $rules);
        $this->assertArrayHasKey('street', $rules);
        $this->assertArrayHasKey('city', $rules);
    }

    /** @test */
    public function store_address_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(StoreAddressRequest::class, 'getFullAddress'));
        $this->assertTrue(method_exists(StoreAddressRequest::class, 'hasCoordinates'));
        $this->assertTrue(method_exists(StoreAddressRequest::class, 'isDefault'));
    }

    /** @test */
    public function calculate_shipping_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(CalculateShippingRequest::class, FormRequest::class));
    }

    /** @test */
    public function calculate_shipping_request_has_validation_rules()
    {
        $request = new CalculateShippingRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('city_id', $rules);
        $this->assertArrayHasKey('merchant_ids', $rules);
    }

    /** @test */
    public function calculate_shipping_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(CalculateShippingRequest::class, 'getCityId'));
        $this->assertTrue(method_exists(CalculateShippingRequest::class, 'getMerchantIds'));
    }

    // =========================================================================
    // IDENTITY REQUESTS
    // =========================================================================

    /** @test */
    public function update_profile_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(UpdateProfileRequest::class, FormRequest::class));
    }

    /** @test */
    public function update_profile_request_has_validation_rules()
    {
        $request = new UpdateProfileRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
    }

    /** @test */
    public function update_profile_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(UpdateProfileRequest::class, 'hasNewAvatar'));
    }

    /** @test */
    public function change_password_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(ChangePasswordRequest::class, FormRequest::class));
    }

    /** @test */
    public function change_password_request_has_validation_rules()
    {
        $request = new ChangePasswordRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('current_password', $rules);
        $this->assertArrayHasKey('password', $rules);
    }

    /** @test */
    public function change_password_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(ChangePasswordRequest::class, 'getHashedPassword'));
    }

    /** @test */
    public function register_request_extends_form_request()
    {
        $this->assertTrue(is_subclass_of(RegisterRequest::class, FormRequest::class));
    }

    /** @test */
    public function register_request_has_validation_rules()
    {
        $request = new RegisterRequest();
        $rules = $request->rules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('password', $rules);
        $this->assertArrayHasKey('terms', $rules);
    }

    /** @test */
    public function register_request_has_helper_methods()
    {
        $this->assertTrue(method_exists(RegisterRequest::class, 'getRole'));
        $this->assertTrue(method_exists(RegisterRequest::class, 'isMerchantRegistration'));
        $this->assertTrue(method_exists(RegisterRequest::class, 'getReferrerId'));
    }

    // =========================================================================
    // FORM REQUEST COMMON FEATURES
    // =========================================================================

    /** @test */
    public function all_requests_have_authorize_method()
    {
        $requests = [
            AddToCartRequest::class,
            UpdateCartItemRequest::class,
            CheckoutRequest::class,
            UpdateStockRequest::class,
            UpdatePriceRequest::class,
            StoreBranchRequest::class,
            StoreReviewRequest::class,
            SearchRequest::class,
            StoreAddressRequest::class,
            CalculateShippingRequest::class,
            UpdateProfileRequest::class,
            ChangePasswordRequest::class,
            RegisterRequest::class,
        ];

        foreach ($requests as $requestClass) {
            $this->assertTrue(
                method_exists($requestClass, 'authorize'),
                "{$requestClass} should have authorize() method"
            );
        }
    }

    /** @test */
    public function all_requests_have_rules_method()
    {
        $requests = [
            AddToCartRequest::class,
            UpdateCartItemRequest::class,
            CheckoutRequest::class,
            UpdateStockRequest::class,
            UpdatePriceRequest::class,
            StoreBranchRequest::class,
            StoreReviewRequest::class,
            SearchRequest::class,
            StoreAddressRequest::class,
            CalculateShippingRequest::class,
            UpdateProfileRequest::class,
            ChangePasswordRequest::class,
            RegisterRequest::class,
        ];

        foreach ($requests as $requestClass) {
            $request = new $requestClass();
            $rules = $request->rules();

            $this->assertIsArray($rules, "{$requestClass}::rules() should return an array");
            $this->assertNotEmpty($rules, "{$requestClass}::rules() should not be empty");
        }
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function commerce_requests_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Requests'));
    }

    /** @test */
    public function merchant_requests_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Requests'));
    }

    /** @test */
    public function catalog_requests_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Requests'));
    }

    /** @test */
    public function shipping_requests_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Requests'));
    }

    /** @test */
    public function identity_requests_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Requests'));
    }
}

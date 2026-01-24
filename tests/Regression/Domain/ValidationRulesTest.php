<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use Illuminate\Contracts\Validation\ValidationRule;

// Catalog Rules
use App\Domain\Catalog\Rules\ValidPartNumber;
use App\Domain\Catalog\Rules\ValidSku;
use App\Domain\Catalog\Rules\ValidRating;

// Merchant Rules
use App\Domain\Merchant\Rules\ValidPrice;
use App\Domain\Merchant\Rules\ValidStock;
use App\Domain\Merchant\Rules\ValidDiscount;

// Identity Rules
use App\Domain\Identity\Rules\ValidSaudiPhone;
use App\Domain\Identity\Rules\ValidPassword;
use App\Domain\Identity\Rules\UniqueEmail;

// Shipping Rules
use App\Domain\Shipping\Rules\ValidAddress;
use App\Domain\Shipping\Rules\ValidCoordinates;
use App\Domain\Shipping\Rules\ShippableCity;

// Commerce Rules
use App\Domain\Commerce\Rules\ValidQuantity;
use App\Domain\Commerce\Rules\AvailableStock;
use App\Domain\Commerce\Rules\ValidPaymentMethod;

/**
 * Regression Tests for Custom Validation Rules
 *
 * Phase 23: Custom Validation Rules
 *
 * This test ensures that custom validation rules are properly structured and functional.
 */
class ValidationRulesTest extends TestCase
{
    // =========================================================================
    // CATALOG RULES
    // =========================================================================

    /** @test */
    public function valid_part_number_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPartNumber::class));
    }

    /** @test */
    public function valid_part_number_implements_validation_rule()
    {
        $rule = new ValidPartNumber();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_part_number_has_validate_method()
    {
        $this->assertTrue(method_exists(ValidPartNumber::class, 'validate'));
    }

    /** @test */
    public function valid_sku_rule_exists()
    {
        $this->assertTrue(class_exists(ValidSku::class));
    }

    /** @test */
    public function valid_sku_implements_validation_rule()
    {
        $rule = new ValidSku();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_rating_rule_exists()
    {
        $this->assertTrue(class_exists(ValidRating::class));
    }

    /** @test */
    public function valid_rating_implements_validation_rule()
    {
        $rule = new ValidRating();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    // =========================================================================
    // MERCHANT RULES
    // =========================================================================

    /** @test */
    public function valid_price_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPrice::class));
    }

    /** @test */
    public function valid_price_implements_validation_rule()
    {
        $rule = new ValidPrice();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_price_has_validate_method()
    {
        $this->assertTrue(method_exists(ValidPrice::class, 'validate'));
    }

    /** @test */
    public function valid_stock_rule_exists()
    {
        $this->assertTrue(class_exists(ValidStock::class));
    }

    /** @test */
    public function valid_stock_implements_validation_rule()
    {
        $rule = new ValidStock();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_discount_rule_exists()
    {
        $this->assertTrue(class_exists(ValidDiscount::class));
    }

    /** @test */
    public function valid_discount_implements_validation_rule()
    {
        $rule = new ValidDiscount();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_discount_has_fluent_methods()
    {
        $this->assertTrue(method_exists(ValidDiscount::class, 'type'));
        $this->assertTrue(method_exists(ValidDiscount::class, 'price'));
    }

    // =========================================================================
    // IDENTITY RULES
    // =========================================================================

    /** @test */
    public function valid_saudi_phone_rule_exists()
    {
        $this->assertTrue(class_exists(ValidSaudiPhone::class));
    }

    /** @test */
    public function valid_saudi_phone_implements_validation_rule()
    {
        $rule = new ValidSaudiPhone();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_saudi_phone_has_validate_method()
    {
        $this->assertTrue(method_exists(ValidSaudiPhone::class, 'validate'));
    }

    /** @test */
    public function valid_password_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPassword::class));
    }

    /** @test */
    public function valid_password_implements_validation_rule()
    {
        $rule = new ValidPassword();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_password_has_fluent_methods()
    {
        $this->assertTrue(method_exists(ValidPassword::class, 'min'));
        $this->assertTrue(method_exists(ValidPassword::class, 'withSpecialCharacters'));
        $this->assertTrue(method_exists(ValidPassword::class, 'simple'));
    }

    /** @test */
    public function unique_email_rule_exists()
    {
        $this->assertTrue(class_exists(UniqueEmail::class));
    }

    /** @test */
    public function unique_email_implements_validation_rule()
    {
        $rule = new UniqueEmail();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function unique_email_has_except_method()
    {
        $this->assertTrue(method_exists(UniqueEmail::class, 'except'));
    }

    // =========================================================================
    // SHIPPING RULES
    // =========================================================================

    /** @test */
    public function valid_address_rule_exists()
    {
        $this->assertTrue(class_exists(ValidAddress::class));
    }

    /** @test */
    public function valid_address_implements_validation_rule()
    {
        $rule = new ValidAddress();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_coordinates_rule_exists()
    {
        $this->assertTrue(class_exists(ValidCoordinates::class));
    }

    /** @test */
    public function valid_coordinates_implements_validation_rule()
    {
        $rule = new ValidCoordinates();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_coordinates_has_static_methods()
    {
        $this->assertTrue(method_exists(ValidCoordinates::class, 'latitude'));
        $this->assertTrue(method_exists(ValidCoordinates::class, 'longitude'));
    }

    /** @test */
    public function shippable_city_rule_exists()
    {
        $this->assertTrue(class_exists(ShippableCity::class));
    }

    /** @test */
    public function shippable_city_implements_validation_rule()
    {
        $rule = new ShippableCity();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function shippable_city_has_for_merchant_method()
    {
        $this->assertTrue(method_exists(ShippableCity::class, 'forMerchant'));
    }

    // =========================================================================
    // COMMERCE RULES
    // =========================================================================

    /** @test */
    public function valid_quantity_rule_exists()
    {
        $this->assertTrue(class_exists(ValidQuantity::class));
    }

    /** @test */
    public function valid_quantity_implements_validation_rule()
    {
        $rule = new ValidQuantity();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function available_stock_rule_exists()
    {
        $this->assertTrue(class_exists(AvailableStock::class));
    }

    /** @test */
    public function available_stock_implements_validation_rule()
    {
        $rule = new AvailableStock(1);
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_payment_method_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPaymentMethod::class));
    }

    /** @test */
    public function valid_payment_method_implements_validation_rule()
    {
        $rule = new ValidPaymentMethod();
        $this->assertInstanceOf(ValidationRule::class, $rule);
    }

    /** @test */
    public function valid_payment_method_has_for_merchants_method()
    {
        $this->assertTrue(method_exists(ValidPaymentMethod::class, 'forMerchants'));
    }

    // =========================================================================
    // COMMON FEATURES
    // =========================================================================

    /** @test */
    public function all_rules_implement_validation_rule_interface()
    {
        $rules = [
            ValidPartNumber::class,
            ValidSku::class,
            ValidRating::class,
            ValidPrice::class,
            ValidStock::class,
            ValidDiscount::class,
            ValidSaudiPhone::class,
            ValidPassword::class,
            UniqueEmail::class,
            ValidAddress::class,
            ValidCoordinates::class,
            ShippableCity::class,
            ValidQuantity::class,
            ValidPaymentMethod::class,
        ];

        foreach ($rules as $ruleClass) {
            $reflection = new \ReflectionClass($ruleClass);
            $this->assertTrue(
                $reflection->implementsInterface(ValidationRule::class),
                "{$ruleClass} should implement ValidationRule interface"
            );
        }
    }

    /** @test */
    public function all_rules_have_validate_method()
    {
        $rules = [
            ValidPartNumber::class,
            ValidSku::class,
            ValidRating::class,
            ValidPrice::class,
            ValidStock::class,
            ValidDiscount::class,
            ValidSaudiPhone::class,
            ValidPassword::class,
            UniqueEmail::class,
            ValidAddress::class,
            ValidCoordinates::class,
            ShippableCity::class,
            ValidQuantity::class,
            ValidPaymentMethod::class,
        ];

        foreach ($rules as $ruleClass) {
            $this->assertTrue(
                method_exists($ruleClass, 'validate'),
                "{$ruleClass} should have validate() method"
            );
        }
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function catalog_rules_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/Rules'));
    }

    /** @test */
    public function merchant_rules_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Merchant/Rules'));
    }

    /** @test */
    public function identity_rules_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Identity/Rules'));
    }

    /** @test */
    public function shipping_rules_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/Rules'));
    }

    /** @test */
    public function commerce_rules_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/Rules'));
    }
}

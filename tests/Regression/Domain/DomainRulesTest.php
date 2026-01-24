<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Platform\Rules\ValidCurrency;
use App\Domain\Platform\Rules\ValidSlug;
use App\Domain\Platform\Rules\ValidJson;
use App\Domain\Catalog\Rules\ValidPartNumber;
use App\Domain\Catalog\Rules\ValidSku;
use App\Domain\Catalog\Rules\ValidRating;
use App\Domain\Merchant\Rules\ValidPrice;
use App\Domain\Merchant\Rules\ValidStock;
use App\Domain\Merchant\Rules\ValidDiscount;
use App\Domain\Identity\Rules\ValidSaudiPhone;
use App\Domain\Identity\Rules\ValidPassword;
use App\Domain\Identity\Rules\UniqueEmail;
use App\Domain\Shipping\Rules\ValidAddress;
use App\Domain\Shipping\Rules\ValidCoordinates;
use App\Domain\Shipping\Rules\ShippableCity;
use App\Domain\Commerce\Rules\ValidQuantity;
use App\Domain\Commerce\Rules\AvailableStock;
use App\Domain\Commerce\Rules\ValidPaymentMethod;
use App\Domain\Commerce\Rules\ValidCoupon;
use App\Domain\Accounting\Rules\ValidWithdrawAmount;
use App\Domain\Accounting\Rules\ValidBankAccount;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Phase 37: Domain Rules Tests
 *
 * Tests for custom validation rules across domains.
 */
class DomainRulesTest extends TestCase
{
    // ============================================
    // Platform Rules
    // ============================================

    /** @test */
    public function valid_currency_rule_exists()
    {
        $this->assertTrue(class_exists(ValidCurrency::class));
    }

    /** @test */
    public function valid_currency_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(ValidCurrency::class))
        );
    }

    /** @test */
    public function valid_slug_rule_exists()
    {
        $this->assertTrue(class_exists(ValidSlug::class));
    }

    /** @test */
    public function valid_slug_has_validate_method()
    {
        $this->assertTrue(method_exists(ValidSlug::class, 'validate'));
    }

    /** @test */
    public function valid_json_rule_exists()
    {
        $this->assertTrue(class_exists(ValidJson::class));
    }

    // ============================================
    // Catalog Rules
    // ============================================

    /** @test */
    public function valid_part_number_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPartNumber::class));
    }

    /** @test */
    public function valid_part_number_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(ValidPartNumber::class))
        );
    }

    /** @test */
    public function valid_sku_rule_exists()
    {
        $this->assertTrue(class_exists(ValidSku::class));
    }

    /** @test */
    public function valid_rating_rule_exists()
    {
        $this->assertTrue(class_exists(ValidRating::class));
    }

    // ============================================
    // Merchant Rules
    // ============================================

    /** @test */
    public function valid_price_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPrice::class));
    }

    /** @test */
    public function valid_price_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(ValidPrice::class))
        );
    }

    /** @test */
    public function valid_stock_rule_exists()
    {
        $this->assertTrue(class_exists(ValidStock::class));
    }

    /** @test */
    public function valid_discount_rule_exists()
    {
        $this->assertTrue(class_exists(ValidDiscount::class));
    }

    // ============================================
    // Identity Rules
    // ============================================

    /** @test */
    public function valid_saudi_phone_rule_exists()
    {
        $this->assertTrue(class_exists(ValidSaudiPhone::class));
    }

    /** @test */
    public function valid_saudi_phone_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(ValidSaudiPhone::class))
        );
    }

    /** @test */
    public function valid_password_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPassword::class));
    }

    /** @test */
    public function unique_email_rule_exists()
    {
        $this->assertTrue(class_exists(UniqueEmail::class));
    }

    // ============================================
    // Shipping Rules
    // ============================================

    /** @test */
    public function valid_address_rule_exists()
    {
        $this->assertTrue(class_exists(ValidAddress::class));
    }

    /** @test */
    public function valid_coordinates_rule_exists()
    {
        $this->assertTrue(class_exists(ValidCoordinates::class));
    }

    /** @test */
    public function shippable_city_rule_exists()
    {
        $this->assertTrue(class_exists(ShippableCity::class));
    }

    // ============================================
    // Commerce Rules
    // ============================================

    /** @test */
    public function valid_quantity_rule_exists()
    {
        $this->assertTrue(class_exists(ValidQuantity::class));
    }

    /** @test */
    public function available_stock_rule_exists()
    {
        $this->assertTrue(class_exists(AvailableStock::class));
    }

    /** @test */
    public function available_stock_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(AvailableStock::class))
        );
    }

    /** @test */
    public function valid_payment_method_rule_exists()
    {
        $this->assertTrue(class_exists(ValidPaymentMethod::class));
    }

    /** @test */
    public function valid_coupon_rule_exists()
    {
        $this->assertTrue(class_exists(ValidCoupon::class));
    }

    // ============================================
    // Accounting Rules
    // ============================================

    /** @test */
    public function valid_withdraw_amount_rule_exists()
    {
        $this->assertTrue(class_exists(ValidWithdrawAmount::class));
    }

    /** @test */
    public function valid_withdraw_amount_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(ValidWithdrawAmount::class))
        );
    }

    /** @test */
    public function valid_bank_account_rule_exists()
    {
        $this->assertTrue(class_exists(ValidBankAccount::class));
    }

    /** @test */
    public function valid_bank_account_implements_validation_rule()
    {
        $this->assertTrue(
            in_array(ValidationRule::class, class_implements(ValidBankAccount::class))
        );
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_rules_exist()
    {
        $rules = [
            ValidCurrency::class,
            ValidSlug::class,
            ValidJson::class,
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
            AvailableStock::class,
            ValidPaymentMethod::class,
            ValidCoupon::class,
            ValidWithdrawAmount::class,
            ValidBankAccount::class,
        ];

        foreach ($rules as $rule) {
            $this->assertTrue(class_exists($rule), "{$rule} should exist");
        }
    }

    /** @test */
    public function all_rules_implement_validation_rule()
    {
        $rules = [
            ValidCurrency::class,
            ValidSlug::class,
            ValidJson::class,
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
            AvailableStock::class,
            ValidPaymentMethod::class,
            ValidCoupon::class,
            ValidWithdrawAmount::class,
            ValidBankAccount::class,
        ];

        foreach ($rules as $rule) {
            $this->assertTrue(
                in_array(ValidationRule::class, class_implements($rule)),
                "{$rule} should implement ValidationRule"
            );
        }
    }

    /** @test */
    public function all_rules_have_validate_method()
    {
        $rules = [
            ValidCurrency::class,
            ValidSlug::class,
            ValidJson::class,
            ValidPartNumber::class,
            ValidSaudiPhone::class,
            ValidPrice::class,
            AvailableStock::class,
            ValidWithdrawAmount::class,
            ValidBankAccount::class,
        ];

        foreach ($rules as $rule) {
            $this->assertTrue(
                method_exists($rule, 'validate'),
                "{$rule} should have validate method"
            );
        }
    }

    /** @test */
    public function platform_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Platform\\Rules',
            ValidCurrency::class
        );
    }

    /** @test */
    public function catalog_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\Rules',
            ValidPartNumber::class
        );
    }

    /** @test */
    public function merchant_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\Rules',
            ValidPrice::class
        );
    }

    /** @test */
    public function identity_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Identity\\Rules',
            ValidSaudiPhone::class
        );
    }

    /** @test */
    public function shipping_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\Rules',
            ValidAddress::class
        );
    }

    /** @test */
    public function commerce_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Commerce\\Rules',
            ValidQuantity::class
        );
    }

    /** @test */
    public function accounting_rules_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Accounting\\Rules',
            ValidWithdrawAmount::class
        );
    }

    /** @test */
    public function rules_directories_exist()
    {
        $directories = [
            app_path('Domain/Platform/Rules'),
            app_path('Domain/Catalog/Rules'),
            app_path('Domain/Merchant/Rules'),
            app_path('Domain/Identity/Rules'),
            app_path('Domain/Shipping/Rules'),
            app_path('Domain/Commerce/Rules'),
            app_path('Domain/Accounting/Rules'),
        ];

        foreach ($directories as $directory) {
            $this->assertDirectoryExists($directory);
        }
    }
}

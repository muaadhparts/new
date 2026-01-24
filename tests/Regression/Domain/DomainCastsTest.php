<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Platform\Casts\MoneyCast;
use App\Domain\Platform\Casts\JsonCast;
use App\Domain\Platform\Casts\EncryptedCast;
use App\Domain\Platform\Casts\SettingValueCast;
use App\Domain\Commerce\Casts\CartDataCast;
use App\Domain\Commerce\Casts\PurchaseStatusCast;
use App\Domain\Catalog\Casts\PartNumberCast;
use App\Domain\Catalog\Casts\ImagesCast;
use App\Domain\Shipping\Casts\CoordinatesCast;
use App\Domain\Shipping\Casts\AddressCast;
use App\Domain\Shipping\Casts\TrackingHistoryCast;
use App\Domain\Identity\Casts\PhoneNumberCast;
use App\Domain\Identity\Casts\PermissionsCast;
use App\Domain\Merchant\Casts\PriceCast;
use App\Domain\Merchant\Casts\WorkingHoursCast;
use App\Domain\Accounting\Casts\BalanceCast;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Phase 36: Domain Casts Tests
 *
 * Tests for custom Eloquent casts across domains.
 */
class DomainCastsTest extends TestCase
{
    // ============================================
    // Platform Casts
    // ============================================

    /** @test */
    public function money_cast_exists()
    {
        $this->assertTrue(class_exists(MoneyCast::class));
    }

    /** @test */
    public function money_cast_implements_casts_attributes()
    {
        $this->assertTrue(
            in_array(CastsAttributes::class, class_implements(MoneyCast::class))
        );
    }

    /** @test */
    public function money_cast_has_required_methods()
    {
        $this->assertTrue(method_exists(MoneyCast::class, 'get'));
        $this->assertTrue(method_exists(MoneyCast::class, 'set'));
    }

    /** @test */
    public function json_cast_exists()
    {
        $this->assertTrue(class_exists(JsonCast::class));
    }

    /** @test */
    public function encrypted_cast_exists()
    {
        $this->assertTrue(class_exists(EncryptedCast::class));
    }

    /** @test */
    public function setting_value_cast_exists()
    {
        $this->assertTrue(class_exists(SettingValueCast::class));
    }

    /** @test */
    public function setting_value_cast_implements_interface()
    {
        $this->assertTrue(
            in_array(CastsAttributes::class, class_implements(SettingValueCast::class))
        );
    }

    // ============================================
    // Commerce Casts
    // ============================================

    /** @test */
    public function cart_data_cast_exists()
    {
        $this->assertTrue(class_exists(CartDataCast::class));
    }

    /** @test */
    public function cart_data_cast_implements_interface()
    {
        $this->assertTrue(
            in_array(CastsAttributes::class, class_implements(CartDataCast::class))
        );
    }

    /** @test */
    public function cart_data_cast_has_helper_methods()
    {
        $this->assertTrue(method_exists(CartDataCast::class, 'calculateTotal'));
    }

    /** @test */
    public function purchase_status_cast_exists()
    {
        $this->assertTrue(class_exists(PurchaseStatusCast::class));
    }

    // ============================================
    // Catalog Casts
    // ============================================

    /** @test */
    public function part_number_cast_exists()
    {
        $this->assertTrue(class_exists(PartNumberCast::class));
    }

    /** @test */
    public function part_number_cast_implements_interface()
    {
        $this->assertTrue(
            in_array(CastsAttributes::class, class_implements(PartNumberCast::class))
        );
    }

    /** @test */
    public function images_cast_exists()
    {
        $this->assertTrue(class_exists(ImagesCast::class));
    }

    // ============================================
    // Shipping Casts
    // ============================================

    /** @test */
    public function coordinates_cast_exists()
    {
        $this->assertTrue(class_exists(CoordinatesCast::class));
    }

    /** @test */
    public function address_cast_exists()
    {
        $this->assertTrue(class_exists(AddressCast::class));
    }

    /** @test */
    public function tracking_history_cast_exists()
    {
        $this->assertTrue(class_exists(TrackingHistoryCast::class));
    }

    /** @test */
    public function tracking_history_cast_has_helper_methods()
    {
        $this->assertTrue(method_exists(TrackingHistoryCast::class, 'addEvent'));
        $this->assertTrue(method_exists(TrackingHistoryCast::class, 'getLatestStatus'));
    }

    // ============================================
    // Identity Casts
    // ============================================

    /** @test */
    public function phone_number_cast_exists()
    {
        $this->assertTrue(class_exists(PhoneNumberCast::class));
    }

    /** @test */
    public function permissions_cast_exists()
    {
        $this->assertTrue(class_exists(PermissionsCast::class));
    }

    /** @test */
    public function permissions_cast_has_helper_methods()
    {
        $this->assertTrue(method_exists(PermissionsCast::class, 'hasPermission'));
    }

    // ============================================
    // Merchant Casts
    // ============================================

    /** @test */
    public function price_cast_exists()
    {
        $this->assertTrue(class_exists(PriceCast::class));
    }

    /** @test */
    public function working_hours_cast_exists()
    {
        $this->assertTrue(class_exists(WorkingHoursCast::class));
    }

    /** @test */
    public function working_hours_cast_has_helper_methods()
    {
        $this->assertTrue(method_exists(WorkingHoursCast::class, 'isOpenAt'));
    }

    // ============================================
    // Accounting Casts
    // ============================================

    /** @test */
    public function balance_cast_exists()
    {
        $this->assertTrue(class_exists(BalanceCast::class));
    }

    /** @test */
    public function balance_cast_implements_interface()
    {
        $this->assertTrue(
            in_array(CastsAttributes::class, class_implements(BalanceCast::class))
        );
    }

    /** @test */
    public function balance_cast_has_helper_methods()
    {
        $this->assertTrue(method_exists(BalanceCast::class, 'format'));
        $this->assertTrue(method_exists(BalanceCast::class, 'isSufficient'));
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_casts_exist()
    {
        $casts = [
            MoneyCast::class,
            JsonCast::class,
            EncryptedCast::class,
            SettingValueCast::class,
            CartDataCast::class,
            PurchaseStatusCast::class,
            PartNumberCast::class,
            ImagesCast::class,
            CoordinatesCast::class,
            AddressCast::class,
            TrackingHistoryCast::class,
            PhoneNumberCast::class,
            PermissionsCast::class,
            PriceCast::class,
            WorkingHoursCast::class,
            BalanceCast::class,
        ];

        foreach ($casts as $cast) {
            $this->assertTrue(class_exists($cast), "{$cast} should exist");
        }
    }

    /** @test */
    public function all_casts_implement_interface()
    {
        $casts = [
            MoneyCast::class,
            JsonCast::class,
            EncryptedCast::class,
            SettingValueCast::class,
            CartDataCast::class,
            PurchaseStatusCast::class,
            PartNumberCast::class,
            ImagesCast::class,
            CoordinatesCast::class,
            AddressCast::class,
            TrackingHistoryCast::class,
            PhoneNumberCast::class,
            PermissionsCast::class,
            PriceCast::class,
            WorkingHoursCast::class,
            BalanceCast::class,
        ];

        foreach ($casts as $cast) {
            $this->assertTrue(
                in_array(CastsAttributes::class, class_implements($cast)),
                "{$cast} should implement CastsAttributes"
            );
        }
    }

    /** @test */
    public function platform_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Platform\\Casts',
            MoneyCast::class
        );
    }

    /** @test */
    public function commerce_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Commerce\\Casts',
            CartDataCast::class
        );
    }

    /** @test */
    public function catalog_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\Casts',
            PartNumberCast::class
        );
    }

    /** @test */
    public function shipping_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\Casts',
            CoordinatesCast::class
        );
    }

    /** @test */
    public function identity_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Identity\\Casts',
            PhoneNumberCast::class
        );
    }

    /** @test */
    public function merchant_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\Casts',
            PriceCast::class
        );
    }

    /** @test */
    public function accounting_casts_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Accounting\\Casts',
            BalanceCast::class
        );
    }

    /** @test */
    public function casts_directories_exist()
    {
        $directories = [
            app_path('Domain/Platform/Casts'),
            app_path('Domain/Commerce/Casts'),
            app_path('Domain/Catalog/Casts'),
            app_path('Domain/Shipping/Casts'),
            app_path('Domain/Identity/Casts'),
            app_path('Domain/Merchant/Casts'),
            app_path('Domain/Accounting/Casts'),
        ];

        foreach ($directories as $directory) {
            $this->assertDirectoryExists($directory);
        }
    }
}

<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Platform\ViewComposers\GlobalDataComposer;
use App\Domain\Platform\ViewComposers\SettingsComposer;
use App\Domain\Platform\ViewComposers\MonetaryUnitComposer;
use App\Domain\Catalog\ViewComposers\CategoryComposer;
use App\Domain\Catalog\ViewComposers\BrandComposer;
use App\Domain\Catalog\ViewComposers\FeaturedItemsComposer;
use App\Domain\Commerce\ViewComposers\CartComposer;
use App\Domain\Commerce\ViewComposers\CheckoutComposer;
use App\Domain\Merchant\ViewComposers\DashboardComposer;
use App\Domain\Merchant\ViewComposers\BranchComposer;
use App\Domain\Shipping\ViewComposers\LocationComposer;
use App\Domain\Shipping\ViewComposers\CourierComposer;
use App\Domain\Identity\ViewComposers\UserComposer;
use App\Domain\Identity\ViewComposers\OperatorComposer;

/**
 * Phase 32: View Composers Tests
 *
 * Tests for view composers across domains.
 */
class ViewComposersTest extends TestCase
{
    // ============================================
    // Platform View Composers
    // ============================================

    /** @test */
    public function global_data_composer_exists()
    {
        $this->assertTrue(class_exists(GlobalDataComposer::class));
    }

    /** @test */
    public function global_data_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(GlobalDataComposer::class, 'compose'));
    }

    /** @test */
    public function settings_composer_exists()
    {
        $this->assertTrue(class_exists(SettingsComposer::class));
    }

    /** @test */
    public function settings_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(SettingsComposer::class, 'compose'));
    }

    /** @test */
    public function monetary_unit_composer_exists()
    {
        $this->assertTrue(class_exists(MonetaryUnitComposer::class));
    }

    /** @test */
    public function monetary_unit_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(MonetaryUnitComposer::class, 'compose'));
    }

    // ============================================
    // Catalog View Composers
    // ============================================

    /** @test */
    public function category_composer_exists()
    {
        $this->assertTrue(class_exists(CategoryComposer::class));
    }

    /** @test */
    public function category_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(CategoryComposer::class, 'compose'));
    }

    /** @test */
    public function brand_composer_exists()
    {
        $this->assertTrue(class_exists(BrandComposer::class));
    }

    /** @test */
    public function brand_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(BrandComposer::class, 'compose'));
    }

    /** @test */
    public function featured_items_composer_exists()
    {
        $this->assertTrue(class_exists(FeaturedItemsComposer::class));
    }

    /** @test */
    public function featured_items_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(FeaturedItemsComposer::class, 'compose'));
    }

    // ============================================
    // Commerce View Composers
    // ============================================

    /** @test */
    public function cart_composer_exists()
    {
        $this->assertTrue(class_exists(CartComposer::class));
    }

    /** @test */
    public function cart_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(CartComposer::class, 'compose'));
    }

    /** @test */
    public function checkout_composer_exists()
    {
        $this->assertTrue(class_exists(CheckoutComposer::class));
    }

    /** @test */
    public function checkout_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(CheckoutComposer::class, 'compose'));
    }

    // ============================================
    // Merchant View Composers
    // ============================================

    /** @test */
    public function dashboard_composer_exists()
    {
        $this->assertTrue(class_exists(DashboardComposer::class));
    }

    /** @test */
    public function dashboard_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(DashboardComposer::class, 'compose'));
    }

    /** @test */
    public function branch_composer_exists()
    {
        $this->assertTrue(class_exists(BranchComposer::class));
    }

    /** @test */
    public function branch_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(BranchComposer::class, 'compose'));
    }

    // ============================================
    // Shipping View Composers
    // ============================================

    /** @test */
    public function location_composer_exists()
    {
        $this->assertTrue(class_exists(LocationComposer::class));
    }

    /** @test */
    public function location_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(LocationComposer::class, 'compose'));
    }

    /** @test */
    public function courier_composer_exists()
    {
        $this->assertTrue(class_exists(CourierComposer::class));
    }

    /** @test */
    public function courier_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(CourierComposer::class, 'compose'));
    }

    // ============================================
    // Identity View Composers
    // ============================================

    /** @test */
    public function user_composer_exists()
    {
        $this->assertTrue(class_exists(UserComposer::class));
    }

    /** @test */
    public function user_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(UserComposer::class, 'compose'));
    }

    /** @test */
    public function operator_composer_exists()
    {
        $this->assertTrue(class_exists(OperatorComposer::class));
    }

    /** @test */
    public function operator_composer_has_compose_method()
    {
        $this->assertTrue(method_exists(OperatorComposer::class, 'compose'));
    }

    /** @test */
    public function operator_composer_has_permission_method()
    {
        $this->assertTrue(method_exists(OperatorComposer::class, 'hasPermission'));
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_composers_exist()
    {
        $composers = [
            GlobalDataComposer::class,
            SettingsComposer::class,
            MonetaryUnitComposer::class,
            CategoryComposer::class,
            BrandComposer::class,
            FeaturedItemsComposer::class,
            CartComposer::class,
            CheckoutComposer::class,
            DashboardComposer::class,
            BranchComposer::class,
            LocationComposer::class,
            CourierComposer::class,
            UserComposer::class,
            OperatorComposer::class,
        ];

        foreach ($composers as $composer) {
            $this->assertTrue(class_exists($composer), "{$composer} should exist");
        }
    }

    /** @test */
    public function all_composers_have_compose_method()
    {
        $composers = [
            GlobalDataComposer::class,
            SettingsComposer::class,
            MonetaryUnitComposer::class,
            CategoryComposer::class,
            BrandComposer::class,
            FeaturedItemsComposer::class,
            CartComposer::class,
            CheckoutComposer::class,
            DashboardComposer::class,
            BranchComposer::class,
            LocationComposer::class,
            CourierComposer::class,
            UserComposer::class,
            OperatorComposer::class,
        ];

        foreach ($composers as $composer) {
            $this->assertTrue(
                method_exists($composer, 'compose'),
                "{$composer} should have compose method"
            );
        }
    }

    /** @test */
    public function platform_composers_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Platform\\ViewComposers',
            GlobalDataComposer::class
        );
    }

    /** @test */
    public function catalog_composers_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\ViewComposers',
            CategoryComposer::class
        );
    }

    /** @test */
    public function commerce_composers_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Commerce\\ViewComposers',
            CartComposer::class
        );
    }

    /** @test */
    public function merchant_composers_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\ViewComposers',
            DashboardComposer::class
        );
    }

    /** @test */
    public function shipping_composers_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\ViewComposers',
            LocationComposer::class
        );
    }

    /** @test */
    public function identity_composers_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Identity\\ViewComposers',
            UserComposer::class
        );
    }
}

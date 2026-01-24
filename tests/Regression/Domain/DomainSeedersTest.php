<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use App\Domain\Catalog\Seeders\BrandSeeder;
use App\Domain\Catalog\Seeders\CategorySeeder;
use App\Domain\Catalog\Seeders\CatalogItemSeeder;
use App\Domain\Shipping\Seeders\CountrySeeder;
use App\Domain\Shipping\Seeders\CitySeeder;
use App\Domain\Shipping\Seeders\CourierSeeder;
use App\Domain\Identity\Seeders\OperatorRoleSeeder;
use App\Domain\Identity\Seeders\OperatorSeeder;
use App\Domain\Identity\Seeders\UserSeeder;
use App\Domain\Platform\Seeders\MonetaryUnitSeeder;
use App\Domain\Platform\Seeders\PlatformSettingSeeder;
use App\Domain\Merchant\Seeders\MerchantBranchSeeder;
use App\Domain\Merchant\Seeders\MerchantSettingSeeder;
use Illuminate\Database\Seeder;

/**
 * Phase 31: Domain Seeders Tests
 *
 * Tests for database seeders across domains.
 */
class DomainSeedersTest extends TestCase
{
    // ============================================
    // Catalog Domain Seeders
    // ============================================

    /** @test */
    public function brand_seeder_exists()
    {
        $this->assertTrue(class_exists(BrandSeeder::class));
    }

    /** @test */
    public function brand_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(BrandSeeder::class, Seeder::class));
    }

    /** @test */
    public function brand_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(BrandSeeder::class, 'run'));
    }

    /** @test */
    public function category_seeder_exists()
    {
        $this->assertTrue(class_exists(CategorySeeder::class));
    }

    /** @test */
    public function category_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(CategorySeeder::class, Seeder::class));
    }

    /** @test */
    public function category_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(CategorySeeder::class, 'run'));
    }

    /** @test */
    public function catalog_item_seeder_exists()
    {
        $this->assertTrue(class_exists(CatalogItemSeeder::class));
    }

    /** @test */
    public function catalog_item_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(CatalogItemSeeder::class, Seeder::class));
    }

    // ============================================
    // Shipping Domain Seeders
    // ============================================

    /** @test */
    public function country_seeder_exists()
    {
        $this->assertTrue(class_exists(CountrySeeder::class));
    }

    /** @test */
    public function country_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(CountrySeeder::class, Seeder::class));
    }

    /** @test */
    public function country_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(CountrySeeder::class, 'run'));
    }

    /** @test */
    public function city_seeder_exists()
    {
        $this->assertTrue(class_exists(CitySeeder::class));
    }

    /** @test */
    public function city_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(CitySeeder::class, Seeder::class));
    }

    /** @test */
    public function city_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(CitySeeder::class, 'run'));
    }

    /** @test */
    public function courier_seeder_exists()
    {
        $this->assertTrue(class_exists(CourierSeeder::class));
    }

    /** @test */
    public function courier_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(CourierSeeder::class, Seeder::class));
    }

    // ============================================
    // Identity Domain Seeders
    // ============================================

    /** @test */
    public function operator_role_seeder_exists()
    {
        $this->assertTrue(class_exists(OperatorRoleSeeder::class));
    }

    /** @test */
    public function operator_role_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(OperatorRoleSeeder::class, Seeder::class));
    }

    /** @test */
    public function operator_role_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(OperatorRoleSeeder::class, 'run'));
    }

    /** @test */
    public function operator_seeder_exists()
    {
        $this->assertTrue(class_exists(OperatorSeeder::class));
    }

    /** @test */
    public function operator_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(OperatorSeeder::class, Seeder::class));
    }

    /** @test */
    public function user_seeder_exists()
    {
        $this->assertTrue(class_exists(UserSeeder::class));
    }

    /** @test */
    public function user_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(UserSeeder::class, Seeder::class));
    }

    /** @test */
    public function user_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(UserSeeder::class, 'run'));
    }

    // ============================================
    // Platform Domain Seeders
    // ============================================

    /** @test */
    public function monetary_unit_seeder_exists()
    {
        $this->assertTrue(class_exists(MonetaryUnitSeeder::class));
    }

    /** @test */
    public function monetary_unit_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(MonetaryUnitSeeder::class, Seeder::class));
    }

    /** @test */
    public function monetary_unit_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(MonetaryUnitSeeder::class, 'run'));
    }

    /** @test */
    public function platform_setting_seeder_exists()
    {
        $this->assertTrue(class_exists(PlatformSettingSeeder::class));
    }

    /** @test */
    public function platform_setting_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(PlatformSettingSeeder::class, Seeder::class));
    }

    /** @test */
    public function platform_setting_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(PlatformSettingSeeder::class, 'run'));
    }

    // ============================================
    // Merchant Domain Seeders
    // ============================================

    /** @test */
    public function merchant_branch_seeder_exists()
    {
        $this->assertTrue(class_exists(MerchantBranchSeeder::class));
    }

    /** @test */
    public function merchant_branch_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(MerchantBranchSeeder::class, Seeder::class));
    }

    /** @test */
    public function merchant_branch_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(MerchantBranchSeeder::class, 'run'));
    }

    /** @test */
    public function merchant_setting_seeder_exists()
    {
        $this->assertTrue(class_exists(MerchantSettingSeeder::class));
    }

    /** @test */
    public function merchant_setting_seeder_extends_seeder()
    {
        $this->assertTrue(is_subclass_of(MerchantSettingSeeder::class, Seeder::class));
    }

    /** @test */
    public function merchant_setting_seeder_has_run_method()
    {
        $this->assertTrue(method_exists(MerchantSettingSeeder::class, 'run'));
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_seeders_exist()
    {
        $seeders = [
            BrandSeeder::class,
            CategorySeeder::class,
            CatalogItemSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            CourierSeeder::class,
            OperatorRoleSeeder::class,
            OperatorSeeder::class,
            UserSeeder::class,
            MonetaryUnitSeeder::class,
            PlatformSettingSeeder::class,
            MerchantBranchSeeder::class,
            MerchantSettingSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->assertTrue(class_exists($seeder), "{$seeder} should exist");
        }
    }

    /** @test */
    public function all_seeders_extend_base_seeder()
    {
        $seeders = [
            BrandSeeder::class,
            CategorySeeder::class,
            CatalogItemSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            CourierSeeder::class,
            OperatorRoleSeeder::class,
            OperatorSeeder::class,
            UserSeeder::class,
            MonetaryUnitSeeder::class,
            PlatformSettingSeeder::class,
            MerchantBranchSeeder::class,
            MerchantSettingSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->assertTrue(
                is_subclass_of($seeder, Seeder::class),
                "{$seeder} should extend Seeder"
            );
        }
    }

    /** @test */
    public function all_seeders_have_run_method()
    {
        $seeders = [
            BrandSeeder::class,
            CategorySeeder::class,
            CatalogItemSeeder::class,
            CountrySeeder::class,
            CitySeeder::class,
            CourierSeeder::class,
            OperatorRoleSeeder::class,
            OperatorSeeder::class,
            UserSeeder::class,
            MonetaryUnitSeeder::class,
            PlatformSettingSeeder::class,
            MerchantBranchSeeder::class,
            MerchantSettingSeeder::class,
        ];

        foreach ($seeders as $seeder) {
            $this->assertTrue(
                method_exists($seeder, 'run'),
                "{$seeder} should have run method"
            );
        }
    }

    /** @test */
    public function catalog_seeders_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Catalog\\Seeders',
            BrandSeeder::class
        );
    }

    /** @test */
    public function shipping_seeders_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Shipping\\Seeders',
            CountrySeeder::class
        );
    }

    /** @test */
    public function identity_seeders_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Identity\\Seeders',
            UserSeeder::class
        );
    }

    /** @test */
    public function platform_seeders_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Platform\\Seeders',
            MonetaryUnitSeeder::class
        );
    }

    /** @test */
    public function merchant_seeders_are_in_correct_namespace()
    {
        $this->assertStringStartsWith(
            'App\\Domain\\Merchant\\Seeders',
            MerchantBranchSeeder::class
        );
    }
}

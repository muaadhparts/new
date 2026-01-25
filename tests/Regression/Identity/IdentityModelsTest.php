<?php

namespace Tests\Regression\Identity;

use Tests\TestCase;
use App\Domain\Identity\Models\User;
use App\Domain\Identity\Models\Operator;
use App\Domain\Identity\Models\OperatorRole;
use App\Domain\Identity\Models\Courier;
use App\Domain\Identity\Models\OauthAccount;

class IdentityModelsTest extends TestCase
{
    /**
     * Test that Domain models load correctly
     */
    public function test_domain_models_load(): void
    {
        $user = User::first();
        $this->assertNotNull($user);

        $operator = Operator::first();
        $this->assertNotNull($operator);

        $role = OperatorRole::first();
        $this->assertNotNull($role);
    }

    /**
     * Test User model functionality
     */
    public function test_user_model_works(): void
    {
        $user = User::first();

        $this->assertNotNull($user);
        $this->assertIsString($user->name);
        $this->assertIsString($user->email);

        // Test IsMerchant method
        $isMerchant = $user->IsMerchant();
        $this->assertIsBool($isMerchant);
    }

    /**
     * Test User merchant relations
     */
    public function test_user_merchant_relations(): void
    {
        $merchant = User::where('is_merchant', 2)->first();

        if ($merchant) {
            $this->assertTrue($merchant->IsMerchant());

            // Test merchantItems relation
            $merchantItems = $merchant->merchantItems;
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $merchantItems);
        } else {
            $this->markTestSkipped('No merchant user found');
        }
    }

    /**
     * Test User buyer relations
     */
    public function test_user_buyer_relations(): void
    {
        $user = User::first();

        // Test purchases relation
        $purchases = $user->purchases;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $purchases);

        // Test favorites relation
        $favorites = $user->favorites;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $favorites);
    }

    /**
     * Test Operator model functionality
     */
    public function test_operator_model_works(): void
    {
        $operator = Operator::first();

        $this->assertNotNull($operator);
        $this->assertIsString($operator->name);
        $this->assertIsString($operator->email);

        // Test IsSuper method
        $isSuper = $operator->IsSuper();
        $this->assertIsBool($isSuper);

        // First operator should be super
        if ($operator->id == 1) {
            $this->assertTrue($isSuper);
        }
    }

    /**
     * Test Operator role relation
     */
    public function test_operator_role_relation(): void
    {
        $operator = Operator::first();

        // Test role relation
        $role = $operator->role;
        $this->assertNotNull($role);
        $this->assertInstanceOf(OperatorRole::class, $role);
    }

    /**
     * Test OperatorRole model functionality
     */
    public function test_operator_role_model_works(): void
    {
        $role = OperatorRole::first();

        $this->assertNotNull($role);
        $this->assertIsString($role->name);

        // Test operators relation
        $operators = $role->operators;
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $operators);
    }

    /**
     * Test OperatorRole sectionCheck method
     */
    public function test_operator_role_section_check(): void
    {
        $role = OperatorRole::first();

        if ($role && $role->section) {
            $sections = explode(" , ", $role->section);
            if (count($sections) > 0) {
                $hasSection = $role->sectionCheck($sections[0]);
                $this->assertTrue($hasSection);
            }
        }

        // Test non-existent section
        $hasSection = $role->sectionCheck('non_existent_section_xyz');
        $this->assertFalse($hasSection);
    }

    /**
     * Test Courier model functionality
     */
    public function test_courier_model_works(): void
    {
        $courier = Courier::first();

        if ($courier) {
            $this->assertInstanceOf(Courier::class, $courier);
            $this->assertIsString($courier->name);

            // Test balance methods
            $balance = $courier->getCurrentBalance();
            $this->assertIsFloat($balance);

            $isInDebt = $courier->isInDebt();
            $this->assertIsBool($isInDebt);

            $hasCredit = $courier->hasCredit();
            $this->assertIsBool($hasCredit);
        } else {
            $this->markTestSkipped('No courier found');
        }
    }

    /**
     * Test Courier scopes
     */
    public function test_courier_scopes_work(): void
    {
        // Test active scope
        $activeCouriers = Courier::active()->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $activeCouriers);

        // Test inDebt scope
        $inDebtCouriers = Courier::inDebt()->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $inDebtCouriers);

        // Test scopeHasCredit via query builder (hasCredit method exists as instance method)
        $creditCouriers = Courier::where('balance', '>', 0)->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $creditCouriers);
    }

    /**
     * Test Courier service areas
     */
    public function test_courier_service_areas(): void
    {
        $courier = Courier::has('serviceAreas')->first();

        if ($courier) {
            $serviceAreas = $courier->serviceAreas;
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $serviceAreas);
            $this->assertGreaterThan(0, $serviceAreas->count());

            // Test servesCity method
            $firstArea = $serviceAreas->first();
            $serves = $courier->servesCity($firstArea->city_id);
            $this->assertTrue($serves);

            // Test getDeliveryFeeForCity method
            $fee = $courier->getDeliveryFeeForCity($firstArea->city_id);
            $this->assertNotNull($fee);
        } else {
            $this->markTestSkipped('No courier with service areas found');
        }
    }

    /**
     * Test OauthAccount model functionality
     */
    public function test_oauth_account_model_works(): void
    {
        $oauthAccount = OauthAccount::first();

        if ($oauthAccount) {
            $this->assertInstanceOf(OauthAccount::class, $oauthAccount);

            // Test user relation
            $user = $oauthAccount->user;
            $this->assertNotNull($user);
        } else {
            $this->markTestSkipped('No OAuth account found');
        }
    }

    /**
     * Test User JWT interface
     */
    public function test_user_jwt_interface(): void
    {
        $user = User::first();

        // Test JWT methods
        $identifier = $user->getJWTIdentifier();
        $this->assertEquals($user->id, $identifier);

        $claims = $user->getJWTCustomClaims();
        $this->assertIsArray($claims);
    }
}

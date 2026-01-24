<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;

// Platform Value Objects
use App\Domain\Platform\ValueObjects\Money;
use App\Domain\Platform\ValueObjects\PhoneNumber;

// Shipping Value Objects
use App\Domain\Shipping\ValueObjects\Address;
use App\Domain\Shipping\ValueObjects\Coordinates;

// Catalog Value Objects
use App\Domain\Catalog\ValueObjects\PartNumber;

// Commerce Value Objects
use App\Domain\Commerce\ValueObjects\Quantity;

/**
 * Regression Tests for Domain Value Objects
 *
 * Phase 13: Value Objects
 *
 * This test ensures that value objects are properly structured and functional.
 */
class ValueObjectsTest extends TestCase
{
    // =========================================================================
    // MONEY VALUE OBJECT
    // =========================================================================

    /** @test */
    public function money_can_be_created()
    {
        $money = Money::of(100.50, 'SAR');

        $this->assertEquals(100.50, $money->amount());
        $this->assertEquals('SAR', $money->currency());
    }

    /** @test */
    public function money_can_be_zero()
    {
        $money = Money::zero('SAR');

        $this->assertEquals(0, $money->amount());
        $this->assertTrue($money->isZero());
    }

    /** @test */
    public function money_can_be_added()
    {
        $money1 = Money::of(100, 'SAR');
        $money2 = Money::of(50, 'SAR');

        $result = $money1->add($money2);

        $this->assertEquals(150, $result->amount());
    }

    /** @test */
    public function money_can_be_subtracted()
    {
        $money1 = Money::of(100, 'SAR');
        $money2 = Money::of(30, 'SAR');

        $result = $money1->subtract($money2);

        $this->assertEquals(70, $result->amount());
    }

    /** @test */
    public function money_can_be_multiplied()
    {
        $money = Money::of(100, 'SAR');

        $result = $money->multiply(2.5);

        $this->assertEquals(250, $result->amount());
    }

    /** @test */
    public function money_can_apply_discount()
    {
        $money = Money::of(100, 'SAR');

        $result = $money->discount(20);

        $this->assertEquals(80, $result->amount());
    }

    /** @test */
    public function money_is_immutable()
    {
        $money = Money::of(100, 'SAR');
        $money->add(Money::of(50, 'SAR'));

        $this->assertEquals(100, $money->amount());
    }

    // =========================================================================
    // PHONE NUMBER VALUE OBJECT
    // =========================================================================

    /** @test */
    public function phone_number_can_be_created()
    {
        $phone = PhoneNumber::from('+966501234567');

        $this->assertEquals('+966501234567', $phone->normalized());
        $this->assertTrue($phone->hasCountryCode());
    }

    /** @test */
    public function phone_number_saudi_format()
    {
        $phone = PhoneNumber::saudi('0501234567');

        $this->assertTrue($phone->isSaudi());
        $this->assertTrue($phone->hasCountryCode());
    }

    /** @test */
    public function phone_number_has_helper_methods()
    {
        $this->assertTrue(method_exists(PhoneNumber::class, 'normalized'));
        $this->assertTrue(method_exists(PhoneNumber::class, 'formatted'));
        $this->assertTrue(method_exists(PhoneNumber::class, 'whatsappLink'));
        $this->assertTrue(method_exists(PhoneNumber::class, 'telLink'));
        $this->assertTrue(method_exists(PhoneNumber::class, 'isSaudi'));
    }

    // =========================================================================
    // ADDRESS VALUE OBJECT
    // =========================================================================

    /** @test */
    public function address_can_be_created()
    {
        $address = Address::create(
            '123 Main St',
            'Riyadh',
            'Saudi Arabia'
        );

        $this->assertEquals('123 Main St', $address->street());
        $this->assertEquals('Riyadh', $address->city());
        $this->assertEquals('Saudi Arabia', $address->country());
    }

    /** @test */
    public function address_can_be_created_from_array()
    {
        $address = Address::fromArray([
            'street' => '123 Main St',
            'city' => 'Riyadh',
            'country' => 'Saudi Arabia',
            'postal_code' => '12345',
        ]);

        $this->assertEquals('12345', $address->postalCode());
    }

    /** @test */
    public function address_full_address_string()
    {
        $address = Address::create(
            '123 Main St',
            'Riyadh',
            'Saudi Arabia',
            null,
            '12345'
        );

        $full = $address->fullAddress();

        $this->assertStringContainsString('123 Main St', $full);
        $this->assertStringContainsString('Riyadh', $full);
        $this->assertStringContainsString('Saudi Arabia', $full);
    }

    /** @test */
    public function address_has_helper_methods()
    {
        $this->assertTrue(method_exists(Address::class, 'fullAddress'));
        $this->assertTrue(method_exists(Address::class, 'shippingLabel'));
        $this->assertTrue(method_exists(Address::class, 'equals'));
        $this->assertTrue(method_exists(Address::class, 'toArray'));
    }

    // =========================================================================
    // COORDINATES VALUE OBJECT
    // =========================================================================

    /** @test */
    public function coordinates_can_be_created()
    {
        $coords = Coordinates::create(24.7136, 46.6753);

        $this->assertEquals(24.7136, $coords->latitude());
        $this->assertEquals(46.6753, $coords->longitude());
    }

    /** @test */
    public function coordinates_can_be_created_from_string()
    {
        $coords = Coordinates::fromString('24.7136,46.6753');

        $this->assertEquals(24.7136, $coords->lat());
        $this->assertEquals(46.6753, $coords->lng());
    }

    /** @test */
    public function coordinates_can_calculate_distance()
    {
        $riyadh = Coordinates::create(24.7136, 46.6753);
        $jeddah = Coordinates::create(21.4858, 39.1925);

        $distance = $riyadh->distanceTo($jeddah);

        // Riyadh to Jeddah is approximately 845km
        $this->assertGreaterThan(800, $distance);
        $this->assertLessThan(900, $distance);
    }

    /** @test */
    public function coordinates_has_helper_methods()
    {
        $this->assertTrue(method_exists(Coordinates::class, 'distanceTo'));
        $this->assertTrue(method_exists(Coordinates::class, 'isWithinRadius'));
        $this->assertTrue(method_exists(Coordinates::class, 'googleMapsUrl'));
        $this->assertTrue(method_exists(Coordinates::class, 'equals'));
    }

    // =========================================================================
    // PART NUMBER VALUE OBJECT
    // =========================================================================

    /** @test */
    public function part_number_can_be_created()
    {
        $partNumber = PartNumber::from('04465-33450');

        $this->assertEquals('04465-33450', $partNumber->value());
    }

    /** @test */
    public function part_number_normalizes_value()
    {
        $partNumber = PartNumber::from('04465-33450');

        $this->assertEquals('0446533450', $partNumber->normalized());
    }

    /** @test */
    public function part_number_matches_different_formats()
    {
        $partNumber1 = PartNumber::from('04465-33450');
        $partNumber2 = PartNumber::from('0446533450');

        $this->assertTrue($partNumber1->matches($partNumber2));
    }

    /** @test */
    public function part_number_has_helper_methods()
    {
        $this->assertTrue(method_exists(PartNumber::class, 'normalized'));
        $this->assertTrue(method_exists(PartNumber::class, 'matches'));
        $this->assertTrue(method_exists(PartNumber::class, 'contains'));
        $this->assertTrue(method_exists(PartNumber::class, 'startsWith'));
        $this->assertTrue(method_exists(PartNumber::class, 'brandPrefix'));
    }

    // =========================================================================
    // QUANTITY VALUE OBJECT
    // =========================================================================

    /** @test */
    public function quantity_can_be_created()
    {
        $qty = Quantity::of(5, 1);

        $this->assertEquals(5, $qty->value());
        $this->assertEquals(1, $qty->minimum());
    }

    /** @test */
    public function quantity_can_be_added()
    {
        $qty = Quantity::of(5);

        $result = $qty->add(3);

        $this->assertEquals(8, $result->value());
    }

    /** @test */
    public function quantity_can_be_subtracted()
    {
        $qty = Quantity::of(5);

        $result = $qty->subtract(2);

        $this->assertEquals(3, $result->value());
    }

    /** @test */
    public function quantity_can_increment_decrement()
    {
        $qty = Quantity::of(5);

        $this->assertEquals(6, $qty->increment()->value());
        $this->assertEquals(4, $qty->decrement()->value());
    }

    /** @test */
    public function quantity_is_immutable()
    {
        $qty = Quantity::of(5);
        $qty->add(3);

        $this->assertEquals(5, $qty->value());
    }

    /** @test */
    public function quantity_has_helper_methods()
    {
        $this->assertTrue(method_exists(Quantity::class, 'isZero'));
        $this->assertTrue(method_exists(Quantity::class, 'isPositive'));
        $this->assertTrue(method_exists(Quantity::class, 'meetsMinimum'));
        $this->assertTrue(method_exists(Quantity::class, 'exceeds'));
    }

    // =========================================================================
    // DIRECTORY STRUCTURE
    // =========================================================================

    /** @test */
    public function platform_value_objects_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Platform/ValueObjects'));
    }

    /** @test */
    public function shipping_value_objects_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Shipping/ValueObjects'));
    }

    /** @test */
    public function catalog_value_objects_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Catalog/ValueObjects'));
    }

    /** @test */
    public function commerce_value_objects_directory_exists()
    {
        $this->assertDirectoryExists(app_path('Domain/Commerce/ValueObjects'));
    }
}

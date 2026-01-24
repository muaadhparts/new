<?php

namespace Tests\Regression\Domain;

use Tests\TestCase;
use Illuminate\Database\Eloquent\Model;
use App\Domain\Platform\Casts\MoneyCast;
use App\Domain\Platform\Casts\JsonCast;
use App\Domain\Platform\Casts\EncryptedCast;
use App\Domain\Shipping\Casts\CoordinatesCast;
use App\Domain\Shipping\Casts\AddressCast;
use App\Domain\Catalog\Casts\PartNumberCast;
use App\Domain\Catalog\Casts\ImagesCast;
use App\Domain\Commerce\Casts\CartDataCast;
use App\Domain\Commerce\Casts\PurchaseStatusCast;
use App\Domain\Identity\Casts\PhoneNumberCast;
use App\Domain\Merchant\Casts\PriceCast;

/**
 * Phase 24: Eloquent Casts Tests
 *
 * Tests for custom Eloquent attribute casts across all domains.
 */
class EloquentCastsTest extends TestCase
{
    // ============================================
    // Platform Domain Casts
    // ============================================

    /** @test */
    public function money_cast_exists()
    {
        $this->assertTrue(class_exists(MoneyCast::class));
    }

    /** @test */
    public function money_cast_implements_casts_attributes()
    {
        $cast = new MoneyCast();
        $this->assertInstanceOf(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class, $cast);
    }

    /** @test */
    public function money_cast_handles_null_values()
    {
        $cast = new MoneyCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'price', null, []);
        $this->assertNull($result);
    }

    /** @test */
    public function money_cast_accepts_currency_parameter()
    {
        $cast = new MoneyCast('USD');
        $this->assertInstanceOf(MoneyCast::class, $cast);
    }

    /** @test */
    public function json_cast_exists()
    {
        $this->assertTrue(class_exists(JsonCast::class));
    }

    /** @test */
    public function json_cast_returns_default_for_null()
    {
        $cast = new JsonCast(['default' => 'value']);
        $model = $this->createMockModel();

        $result = $cast->get($model, 'data', null, []);
        $this->assertEquals(['default' => 'value'], $result);
    }

    /** @test */
    public function json_cast_decodes_valid_json()
    {
        $cast = new JsonCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'data', '{"key":"value"}', []);
        $this->assertEquals(['key' => 'value'], $result);
    }

    /** @test */
    public function json_cast_returns_default_for_invalid_json()
    {
        $cast = new JsonCast([]);
        $model = $this->createMockModel();

        $result = $cast->get($model, 'data', 'invalid json{', []);
        $this->assertEquals([], $result);
    }

    /** @test */
    public function json_cast_encodes_on_set()
    {
        $cast = new JsonCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'data', ['key' => 'value'], []);
        $this->assertJson($result);
    }

    /** @test */
    public function encrypted_cast_exists()
    {
        $this->assertTrue(class_exists(EncryptedCast::class));
    }

    /** @test */
    public function encrypted_cast_handles_null()
    {
        $cast = new EncryptedCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'secret', null, []);
        $this->assertNull($result);
    }

    /** @test */
    public function encrypted_cast_encrypts_on_set()
    {
        $cast = new EncryptedCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'secret', 'my-secret', []);
        $this->assertNotEquals('my-secret', $result);
        $this->assertNotNull($result);
    }

    // ============================================
    // Shipping Domain Casts
    // ============================================

    /** @test */
    public function coordinates_cast_exists()
    {
        $this->assertTrue(class_exists(CoordinatesCast::class));
    }

    /** @test */
    public function coordinates_cast_accepts_key_parameters()
    {
        $cast = new CoordinatesCast('lat', 'lng');
        $this->assertInstanceOf(CoordinatesCast::class, $cast);
    }

    /** @test */
    public function coordinates_cast_returns_null_when_missing()
    {
        $cast = new CoordinatesCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'coordinates', null, []);
        $this->assertNull($result);
    }

    /** @test */
    public function coordinates_cast_sets_both_values()
    {
        $cast = new CoordinatesCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'coordinates', null, []);
        $this->assertArrayHasKey('latitude', $result);
        $this->assertArrayHasKey('longitude', $result);
    }

    /** @test */
    public function address_cast_exists()
    {
        $this->assertTrue(class_exists(AddressCast::class));
    }

    /** @test */
    public function address_cast_handles_null()
    {
        $cast = new AddressCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'address', null, []);
        $this->assertNull($result);
    }

    /** @test */
    public function address_cast_decodes_json()
    {
        $cast = new AddressCast();
        $model = $this->createMockModel();

        $json = '{"street":"123 Main St","city":"Riyadh","country":"Saudi Arabia"}';
        $result = $cast->get($model, 'address', $json, []);

        $this->assertNotNull($result);
    }

    /** @test */
    public function address_cast_sets_null()
    {
        $cast = new AddressCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'address', null, []);
        $this->assertNull($result);
    }

    // ============================================
    // Catalog Domain Casts
    // ============================================

    /** @test */
    public function part_number_cast_exists()
    {
        $this->assertTrue(class_exists(PartNumberCast::class));
    }

    /** @test */
    public function part_number_cast_handles_null()
    {
        $cast = new PartNumberCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'part_number', null, []);
        $this->assertNull($result);
    }

    /** @test */
    public function part_number_cast_normalizes_on_set()
    {
        $cast = new PartNumberCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'part_number', 'abc-123', []);
        $this->assertEquals('ABC123', $result);
    }

    /** @test */
    public function part_number_cast_removes_spaces()
    {
        $cast = new PartNumberCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'part_number', 'ABC 123 DEF', []);
        $this->assertEquals('ABC123DEF', $result);
    }

    /** @test */
    public function images_cast_exists()
    {
        $this->assertTrue(class_exists(ImagesCast::class));
    }

    /** @test */
    public function images_cast_returns_empty_array_for_null()
    {
        $cast = new ImagesCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'images', null, []);
        $this->assertEquals([], $result);
    }

    /** @test */
    public function images_cast_decodes_json_array()
    {
        $cast = new ImagesCast();
        $model = $this->createMockModel();

        $json = '["image1.jpg","image2.jpg"]';
        $result = $cast->get($model, 'images', $json, []);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
    }

    /** @test */
    public function images_cast_includes_url_in_result()
    {
        $cast = new ImagesCast();
        $model = $this->createMockModel();

        $json = '["image1.jpg"]';
        $result = $cast->get($model, 'images', $json, []);

        $this->assertArrayHasKey('path', $result[0]);
        $this->assertArrayHasKey('url', $result[0]);
    }

    /** @test */
    public function images_cast_accepts_disk_parameter()
    {
        $cast = new ImagesCast('s3', 'products');
        $this->assertInstanceOf(ImagesCast::class, $cast);
    }

    // ============================================
    // Commerce Domain Casts
    // ============================================

    /** @test */
    public function cart_data_cast_exists()
    {
        $this->assertTrue(class_exists(CartDataCast::class));
    }

    /** @test */
    public function cart_data_cast_returns_empty_array_for_null()
    {
        $cast = new CartDataCast();
        $model = $this->createMockModel();

        $result = $cast->get($model, 'cart', null, []);
        $this->assertEquals([], $result);
    }

    /** @test */
    public function cart_data_cast_normalizes_items()
    {
        $cast = new CartDataCast();
        $model = $this->createMockModel();

        $json = '[{"id":1,"quantity":2,"price":100}]';
        $result = $cast->get($model, 'cart', $json, []);

        $this->assertArrayHasKey('merchant_item_id', $result[0]);
        $this->assertArrayHasKey('quantity', $result[0]);
        $this->assertArrayHasKey('price', $result[0]);
    }

    /** @test */
    public function cart_data_cast_calculates_total()
    {
        $items = [
            ['price' => 100, 'quantity' => 2],
            ['price' => 50, 'quantity' => 3],
        ];

        $total = CartDataCast::calculateTotal($items);
        $this->assertEquals(350.0, $total);
    }

    /** @test */
    public function purchase_status_cast_exists()
    {
        $this->assertTrue(class_exists(PurchaseStatusCast::class));
    }

    /** @test */
    public function purchase_status_cast_normalizes_status()
    {
        $cast = new PurchaseStatusCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'status', '  PENDING  ', []);
        $this->assertEquals('pending', $result);
    }

    /** @test */
    public function purchase_status_cast_has_labels()
    {
        $label = PurchaseStatusCast::getLabel('pending');
        $this->assertEquals('قيد الانتظار', $label);
    }

    /** @test */
    public function purchase_status_cast_has_colors()
    {
        $color = PurchaseStatusCast::getColor('delivered');
        $this->assertEquals('success', $color);
    }

    /** @test */
    public function purchase_status_cast_returns_all_statuses()
    {
        $statuses = PurchaseStatusCast::all();
        $this->assertContains('pending', $statuses);
        $this->assertContains('delivered', $statuses);
        $this->assertContains('cancelled', $statuses);
    }

    // ============================================
    // Identity Domain Casts
    // ============================================

    /** @test */
    public function phone_number_cast_exists()
    {
        $this->assertTrue(class_exists(PhoneNumberCast::class));
    }

    /** @test */
    public function phone_number_cast_handles_null()
    {
        $cast = new PhoneNumberCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'phone', null, []);
        $this->assertNull($result);
    }

    /** @test */
    public function phone_number_cast_normalizes_saudi_number()
    {
        $cast = new PhoneNumberCast();
        $model = $this->createMockModel();

        // Test various formats
        $result1 = $cast->set($model, 'phone', '0512345678', []);
        $result2 = $cast->set($model, 'phone', '+966512345678', []);
        $result3 = $cast->set($model, 'phone', '966512345678', []);

        $this->assertEquals('512345678', $result1);
        $this->assertEquals('512345678', $result2);
        $this->assertEquals('512345678', $result3);
    }

    /** @test */
    public function phone_number_cast_formats_for_display()
    {
        $cast = new PhoneNumberCast();
        $formatted = $cast->formatForDisplay('512345678');

        $this->assertEquals('051 234 5678', $formatted);
    }

    /** @test */
    public function phone_number_cast_formats_international()
    {
        $cast = new PhoneNumberCast();
        $formatted = $cast->formatInternational('512345678');

        $this->assertEquals('+966512345678', $formatted);
    }

    // ============================================
    // Merchant Domain Casts
    // ============================================

    /** @test */
    public function price_cast_exists()
    {
        $this->assertTrue(class_exists(PriceCast::class));
    }

    /** @test */
    public function price_cast_rounds_to_decimals()
    {
        $cast = new PriceCast(2);
        $model = $this->createMockModel();

        $result = $cast->get($model, 'price', 99.999, []);
        $this->assertEquals(100.00, $result);
    }

    /** @test */
    public function price_cast_removes_currency_symbols()
    {
        $cast = new PriceCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'price', '$99.99 SAR', []);
        $this->assertEquals(99.99, $result);
    }

    /** @test */
    public function price_cast_prevents_negative_prices()
    {
        $cast = new PriceCast();
        $model = $this->createMockModel();

        $result = $cast->set($model, 'price', -50, []);
        $this->assertEquals(0, $result);
    }

    /** @test */
    public function price_cast_formats_price()
    {
        $formatted = PriceCast::format(1234.56, 'SAR');
        $this->assertEquals('1,234.56 SAR', $formatted);
    }

    /** @test */
    public function price_cast_applies_percent_discount()
    {
        $discounted = PriceCast::applyDiscount(100, 20, 'percent');
        $this->assertEquals(80.0, $discounted);
    }

    /** @test */
    public function price_cast_applies_fixed_discount()
    {
        $discounted = PriceCast::applyDiscount(100, 30, 'fixed');
        $this->assertEquals(70.0, $discounted);
    }

    // ============================================
    // Integration Tests
    // ============================================

    /** @test */
    public function all_casts_implement_interface()
    {
        $casts = [
            MoneyCast::class,
            JsonCast::class,
            EncryptedCast::class,
            CoordinatesCast::class,
            AddressCast::class,
            PartNumberCast::class,
            ImagesCast::class,
            CartDataCast::class,
            PurchaseStatusCast::class,
            PhoneNumberCast::class,
            PriceCast::class,
        ];

        foreach ($casts as $castClass) {
            $cast = new $castClass();
            $this->assertInstanceOf(
                \Illuminate\Contracts\Database\Eloquent\CastsAttributes::class,
                $cast,
                "{$castClass} should implement CastsAttributes"
            );
        }
    }

    /** @test */
    public function all_casts_have_get_method()
    {
        $casts = [
            MoneyCast::class,
            JsonCast::class,
            EncryptedCast::class,
            CoordinatesCast::class,
            AddressCast::class,
            PartNumberCast::class,
            ImagesCast::class,
            CartDataCast::class,
            PurchaseStatusCast::class,
            PhoneNumberCast::class,
            PriceCast::class,
        ];

        foreach ($casts as $castClass) {
            $this->assertTrue(
                method_exists($castClass, 'get'),
                "{$castClass} should have get method"
            );
        }
    }

    /** @test */
    public function all_casts_have_set_method()
    {
        $casts = [
            MoneyCast::class,
            JsonCast::class,
            EncryptedCast::class,
            CoordinatesCast::class,
            AddressCast::class,
            PartNumberCast::class,
            ImagesCast::class,
            CartDataCast::class,
            PurchaseStatusCast::class,
            PhoneNumberCast::class,
            PriceCast::class,
        ];

        foreach ($casts as $castClass) {
            $this->assertTrue(
                method_exists($castClass, 'set'),
                "{$castClass} should have set method"
            );
        }
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Create a mock model for testing casts
     */
    protected function createMockModel(): Model
    {
        return new class extends Model {
            protected $guarded = [];
        };
    }
}

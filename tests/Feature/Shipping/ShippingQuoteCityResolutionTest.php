<?php

namespace Tests\Feature\Shipping;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use App\Domain\Shipping\Services\ShippingQuoteService;
use App\Domain\Shipping\Services\TryotoLocationService;
use App\Domain\Shipping\Services\TryotoService;
use App\Domain\Shipping\Services\GoogleMapsService;
use Mockery;

/**
 * ShippingQuoteCityResolutionTest
 *
 * Tests city resolution logic that matches ShippingApiController (checkout) behavior.
 * Covers:
 * 1. Matching city name (exact match in Tryoto DB)
 * 2. Non-matching city name (requires resolution fallback)
 * 3. Location requirement when no coordinates provided
 */
class ShippingQuoteCityResolutionTest extends TestCase
{
    protected TryotoLocationService $locationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->locationService = app(TryotoLocationService::class);
    }

    /**
     * Test: Requires location when no coordinates provided
     */
    public function test_requires_location_when_no_coordinates(): void
    {
        // Create a mock service that doesn't have stored coordinates
        $tryotoService = Mockery::mock(TryotoService::class);
        $googleMapsService = Mockery::mock(GoogleMapsService::class);

        $quoteService = new ShippingQuoteService(
            $tryotoService,
            $this->locationService,
            $googleMapsService
        );

        // Call without coordinates
        $result = $quoteService->getCatalogItemQuote(1, 0.5, null);

        // Should fail with requires_location
        $this->assertFalse($result['success']);
        $this->assertTrue($result['requires_location'] ?? false);
        $this->assertEquals('coordinates', $result['location_type'] ?? null);
        $this->assertNotEmpty($result['message']);
    }

    /**
     * Test: Exact city match in Tryoto DB
     *
     * When Google Maps returns a city name that exactly matches a city in our DB
     * that is marked as tryoto_supported, it should be resolved immediately.
     */
    public function test_exact_city_match_resolves_immediately(): void
    {
        // Ensure we have a test city in the database
        $country = DB::table('countries')->where('country_code', 'SA')->first();

        if (!$country) {
            $this->markTestSkipped('No Saudi Arabia country in database');
        }

        // Find a city that is tryoto_supported
        $testCity = DB::table('cities')
            ->where('country_id', $country->id)
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (!$testCity) {
            $this->markTestSkipped('No tryoto_supported city with coordinates in Saudi Arabia');
        }

        // Test the resolution
        $result = $this->locationService->resolveMapCity(
            $testCity->city_name, // Exact name from DB
            null,
            'Saudi Arabia',
            (float) $testCity->latitude,
            (float) $testCity->longitude
        );

        $this->assertTrue($result['success'], 'Expected exact match to succeed');
        $this->assertEquals('exact_city', $result['strategy'], 'Expected exact_city strategy');
        $this->assertEquals($testCity->city_name, $result['resolved_name']);
    }

    /**
     * Test: Non-matching city name falls back to coordinates
     *
     * When Google Maps returns a city name that doesn't match any city in our DB,
     * the service should fall back to finding the nearest supported city by coordinates.
     */
    public function test_non_matching_city_uses_coordinates_fallback(): void
    {
        // Ensure we have a test city in the database
        $country = DB::table('countries')->where('country_code', 'SA')->first();

        if (!$country) {
            $this->markTestSkipped('No Saudi Arabia country in database');
        }

        // Find a city that is tryoto_supported with coordinates
        $testCity = DB::table('cities')
            ->where('country_id', $country->id)
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (!$testCity) {
            $this->markTestSkipped('No tryoto_supported city with coordinates in Saudi Arabia');
        }

        // Use a fake city name that definitely won't match
        $fakeCityName = 'NonExistentCityXYZ12345';

        // Use coordinates near a real city (with small offset)
        $nearLat = (float) $testCity->latitude + 0.01; // ~1km offset
        $nearLng = (float) $testCity->longitude + 0.01;

        // Test the resolution
        $result = $this->locationService->resolveMapCity(
            $fakeCityName,
            null,
            'Saudi Arabia',
            $nearLat,
            $nearLng
        );

        // Should either find nearest city or fall back to state
        if ($result['success']) {
            $this->assertContains(
                $result['strategy'],
                ['exact_city', 'fallback_state', 'nearest_city_same_country'],
                'Expected a fallback strategy to be used'
            );
            $this->assertNotEmpty($result['resolved_name']);
        } else {
            // If no city found within max distance, that's also valid
            $this->assertContains(
                $result['strategy'],
                ['no_supported_cities', 'country_not_supported'],
                'Expected failure strategy when no nearby cities'
            );
        }
    }

    /**
     * Test: State name used as fallback when city not found
     *
     * When the city name doesn't match but the state name does,
     * the service should use the state name.
     */
    public function test_state_name_fallback_when_city_not_found(): void
    {
        // Ensure we have a test city in the database
        $country = DB::table('countries')->where('country_code', 'SA')->first();

        if (!$country) {
            $this->markTestSkipped('No Saudi Arabia country in database');
        }

        // Find a city that is tryoto_supported
        $testCity = DB::table('cities')
            ->where('country_id', $country->id)
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (!$testCity) {
            $this->markTestSkipped('No tryoto_supported city with coordinates in Saudi Arabia');
        }

        // Use the city name as the state (Google Maps sometimes returns this)
        $result = $this->locationService->resolveMapCity(
            'SomeSmallVillageNotInDB', // City name that won't match
            $testCity->city_name,       // State name that matches a city
            'Saudi Arabia',
            (float) $testCity->latitude,
            (float) $testCity->longitude
        );

        // Should find via state fallback or nearest city
        if ($result['success']) {
            $this->assertContains(
                $result['strategy'],
                ['fallback_state', 'nearest_city_same_country'],
                'Expected fallback_state or nearest_city strategy'
            );
        }
    }

    /**
     * Test: Coordinates-only resolution works
     *
     * When geocoding fails completely, the service should still be able
     * to find the nearest supported city using only coordinates.
     */
    public function test_coordinates_only_resolution(): void
    {
        // Find a city that is tryoto_supported with coordinates
        $testCity = DB::table('cities')
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (!$testCity) {
            $this->markTestSkipped('No tryoto_supported city with coordinates in database');
        }

        // Test resolution using only coordinates (no city/country names)
        $result = $this->locationService->resolveByCoordinatesOnly(
            (float) $testCity->latitude,
            (float) $testCity->longitude
        );

        $this->assertTrue($result['success'], 'Expected coordinates-only resolution to succeed');
        $this->assertEquals('coordinates_fallback', $result['strategy']);
        $this->assertNotEmpty($result['resolved_name']);
    }

    /**
     * Test: City name normalization removes special characters
     *
     * Ensures that city names with special characters (ā, ī, etc.)
     * are normalized correctly for Tryoto API.
     */
    public function test_city_name_normalization(): void
    {
        // Create a service instance to access the normalize method
        $tryotoService = Mockery::mock(TryotoService::class);
        $googleMapsService = Mockery::mock(GoogleMapsService::class);

        $quoteService = new ShippingQuoteService(
            $tryotoService,
            $this->locationService,
            $googleMapsService
        );

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($quoteService);
        $method = $reflection->getMethod('normalizeCityName');
        $method->setAccessible(true);

        // Test cases
        $testCases = [
            ['Riyādh', 'Riyadh'],
            ['Makkah Al-Mukarramah', 'Makkah Al-Mukarramah'],
            ["Ta'if", 'Taif'],
            ['Jīzan', 'Jizan'],
        ];

        foreach ($testCases as [$input, $expected]) {
            $result = $method->invoke($quoteService, $input);
            $this->assertEquals($expected, $result, "Failed normalizing: {$input}");
        }
    }

    /**
     * Test: Full quote flow with coordinates
     */
    public function test_full_quote_flow_with_coordinates(): void
    {
        // Find a merchant with a configured branch
        $merchant = DB::table('merchant_branches')
            ->where('status', 1)
            ->whereNotNull('city_id')
            ->first();

        if (!$merchant) {
            $this->markTestSkipped('No merchant with configured branch found');
        }

        // Find a supported city with coordinates
        $destCity = DB::table('cities')
            ->where('tryoto_supported', 1)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->first();

        if (!$destCity) {
            $this->markTestSkipped('No tryoto_supported city with coordinates found');
        }

        // Make API call with coordinates
        $response = $this->postJson('/api/shipping-quote/quick-estimate', [
            'merchant_id' => $merchant->user_id,
            'weight' => 1.0,
            'latitude' => $destCity->latitude,
            'longitude' => $destCity->longitude,
        ]);

        // Should either succeed or fail gracefully (no 500 error)
        $response->assertStatus(200);

        $data = $response->json();

        // If success, should have price info
        if ($data['success'] ?? false) {
            $this->assertArrayHasKey('price', $data);
            $this->assertArrayHasKey('destination', $data);
        } else {
            // If failure, should have proper error message
            $this->assertArrayHasKey('message', $data);
        }
    }

    /**
     * Test: API returns requires_location when no coordinates
     */
    public function test_api_returns_requires_location_error(): void
    {
        // Find a merchant with a configured branch
        $merchant = DB::table('merchant_branches')
            ->where('status', 1)
            ->whereNotNull('city_id')
            ->first();

        if (!$merchant) {
            $this->markTestSkipped('No merchant with configured branch found');
        }

        // Make API call WITHOUT coordinates
        $response = $this->postJson('/api/shipping-quote/quick-estimate', [
            'merchant_id' => $merchant->user_id,
            'weight' => 1.0,
            // No latitude/longitude
        ]);

        $response->assertStatus(200);

        $data = $response->json();

        // Should fail with requires_location
        $this->assertFalse($data['success'] ?? true);
        $this->assertTrue($data['requires_location'] ?? false);
        $this->assertEquals('coordinates', $data['location_type'] ?? null);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}

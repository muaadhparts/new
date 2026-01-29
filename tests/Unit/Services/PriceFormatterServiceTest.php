<?php

namespace Tests\Unit\Services;

use App\Domain\Commerce\Services\PriceFormatterService;
use Tests\TestCase;

class PriceFormatterServiceTest extends TestCase
{
    private PriceFormatterService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PriceFormatterService::class);
    }

    /** @test */
    public function it_formats_price_with_currency_symbol()
    {
        $result = $this->service->format(100);
        
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    /** @test */
    public function it_handles_null_price()
    {
        $result = $this->service->format(null);
        
        $this->assertIsString($result);
    }

    /** @test */
    public function it_converts_price_to_float()
    {
        $result = $this->service->convert(100);
        
        $this->assertIsFloat($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    /** @test */
    public function it_formats_price_with_custom_decimals()
    {
        $result = $this->service->formatWithDecimals(100.5555, 2);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('100.56', $result);
    }

    /** @test */
    public function it_formats_batch_of_prices()
    {
        $prices = [100, 200, 300];
        $results = $this->service->formatBatch($prices);
        
        $this->assertIsArray($results);
        $this->assertCount(3, $results);
        foreach ($results as $result) {
            $this->assertIsString($result);
        }
    }

    /** @test */
    public function it_detects_zero_price()
    {
        $this->assertTrue($this->service->isZero(0));
        $this->assertTrue($this->service->isZero(null));
        $this->assertFalse($this->service->isZero(100));
    }

    /** @test */
    public function it_formats_price_range()
    {
        $result = $this->service->formatRange(100, 200);
        
        $this->assertIsString($result);
        $this->assertStringContainsString('-', $result);
    }

    /** @test */
    public function it_formats_same_price_range_as_single_price()
    {
        $result = $this->service->formatRange(100, 100);
        
        $this->assertIsString($result);
        $this->assertStringNotContainsString('-', $result);
    }

    /** @test */
    public function it_calculates_discount_percentage()
    {
        $result = $this->service->formatDiscountPercentage(100, 80);
        
        $this->assertIsString($result);
        $this->assertEquals('20%', $result);
    }

    /** @test */
    public function it_returns_zero_discount_when_no_discount()
    {
        $result = $this->service->formatDiscountPercentage(100, 100);
        
        $this->assertEquals('0%', $result);
    }

    /** @test */
    public function it_formats_free_price()
    {
        $result = $this->service->formatOrFree(0);
        
        $this->assertIsString($result);
        // Should contain "Free" or translated equivalent
    }

    /** @test */
    public function it_gets_currency_symbol()
    {
        $result = $this->service->getCurrencySymbol();
        
        $this->assertIsString($result);
    }

    /** @test */
    public function it_gets_currency_code()
    {
        $result = $this->service->getCurrencyCode();
        
        $this->assertIsString($result);
    }
}

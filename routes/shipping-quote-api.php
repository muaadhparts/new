<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Front\CustomerLocationController;
use App\Http\Controllers\Api\Front\ShippingQuoteController;

/*
|--------------------------------------------------------------------------
| Shipping Quote API Routes
|--------------------------------------------------------------------------
|
| These routes are for the shipping quote system.
| Independent from Checkout/Cart.
|
*/

// Customer Location API
Route::prefix('api/customer-location')->group(function () {
    Route::get('/status', [CustomerLocationController::class, 'status']);
    Route::post('/manual', [CustomerLocationController::class, 'setManually']);
    Route::post('/geolocation', [CustomerLocationController::class, 'setFromGeolocation']);
    Route::get('/cities', [CustomerLocationController::class, 'getCities']);
    Route::post('/clear', [CustomerLocationController::class, 'clear']);
});

// Shipping Quote API
Route::prefix('api/shipping-quote')->group(function () {
    Route::post('/quote', [ShippingQuoteController::class, 'getQuote']);
    Route::post('/quick-estimate', [ShippingQuoteController::class, 'quickEstimate']);

    // Location management (for shipping quote - uses coordinates from browser)
    Route::post('/store-location', [ShippingQuoteController::class, 'storeLocation']);
    Route::get('/location-status', [ShippingQuoteController::class, 'getLocationStatus']);
    Route::post('/clear-location', [ShippingQuoteController::class, 'clearLocation']);
});

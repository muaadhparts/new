<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GeocodingController;
use App\Http\Controllers\Api\SpecificationApiController;
use App\Http\Controllers\Api\CatalogItemApiController;
use App\Http\Controllers\Api\ShippingApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// --------------------- SPECIFICATION API ROUTES ---------------------
Route::prefix('specs')->middleware(['web'])->group(function () {
    Route::post('/save', [SpecificationApiController::class, 'save'])->name('api.specs.save');
    Route::post('/clear', [SpecificationApiController::class, 'clear'])->name('api.specs.clear');
    Route::get('/current', [SpecificationApiController::class, 'current'])->name('api.specs.current');
});
// --------------------- SPECIFICATION API ROUTES END ---------------------

// --------------------- CATALOG ITEM API ROUTES ---------------------
Route::prefix('catalog-item')->middleware(['web'])->group(function () {
    // Alternatives
    Route::get('/alternatives/{sku}', [CatalogItemApiController::class, 'getAlternatives'])->name('api.catalog-item.alternatives');
    Route::get('/alternatives/{sku}/related', [CatalogItemApiController::class, 'getAlternativeRelatedCatalogItems'])->name('api.catalog-item.alternatives.related');
    Route::get('/alternatives/{sku}/html', [CatalogItemApiController::class, 'getAlternativesHtml'])->name('api.catalog-item.alternatives.html');

    // Compatibility
    Route::get('/compatibility/{sku}', [CatalogItemApiController::class, 'getCompatibility'])->name('api.catalog-item.compatibility');
    Route::get('/compatibility/{sku}/html', [CatalogItemApiController::class, 'getCompatibilityHtml'])->name('api.catalog-item.compatibility.html');
});
// --------------------- CATALOG ITEM API ROUTES END ---------------------

// --------------------- SHIPPING API ROUTES ---------------------
Route::prefix('shipping')->middleware(['web'])->group(function () {
    Route::post('/tryoto/options', [ShippingApiController::class, 'getTryotoOptions'])->name('api.shipping.tryoto.options');
    Route::post('/tryoto/html', [ShippingApiController::class, 'getTryotoHtml'])->name('api.shipping.tryoto.html');
});
// --------------------- SHIPPING API ROUTES END ---------------------

// --------------------- GOOGLE MAPS GEOCODING ROUTES ---------------------
// MOVED TO web.php to share session with checkout
// Routes are now at: /geocoding/reverse, /geocoding/search-cities, etc.
// --------------------- GOOGLE MAPS GEOCODING ROUTES END ---------------------

// --------------------- TRYOTO WEBHOOK ROUTES ---------------------
Route::post('/tryoto/webhook', 'Admin\ShipmentController@webhook')->name('api.tryoto.webhook');
// --------------------- TRYOTO WEBHOOK ROUTES END ---------------------


Route::group(['prefix' => 'user'], function () {

    Route::post('registration', 'Api\Auth\AuthController@register');
    Route::post('login', 'Api\Auth\AuthController@login');
    Route::post('logout', 'Api\Auth\AuthController@logout');
    Route::post('forgot', 'Api\Auth\AuthController@forgot');
    Route::post('forgot/submit', 'Api\Auth\AuthController@forgot_submit');
    Route::post('social/login', 'Api\Auth\AuthController@social_login');
    Route::post('refresh/token', 'Api\Auth\AuthController@refresh');
    Route::get('details', 'Api\Auth\AuthController@details');
    
    Route::group(['middleware' => 'auth:api'], function () {


        // --------------------- USER DASHBOARD ---------------------
        
        Route::get('/dashboard', 'Api\User\ProfileController@dashboard');

        // --------------------- USER DASHBOARD ENDS ---------------------


        // --------------------- USER PROFILE ---------------------

        Route::post('/profile/update', 'Api\User\ProfileController@update');
        Route::post('/password/update', 'Api\User\ProfileController@updatePassword');

        // --------------------- USER PROFILE ENDS ---------------------

        // --------------------- USER FAVORITE ---------------------

        Route::get('/favorite/vendors', 'Api\User\ProfileController@favorites');
        Route::post('/favorite/store', 'Api\User\ProfileController@favorite');
        Route::get('/favorite/delete/{id}', 'Api\User\ProfileController@favdelete');

        // --------------------- USER FAVORITE ENDS ---------------------


        // --------------------- TICKET & DISPUTE ---------------------

        Route::get('/tickets', 'Api\User\TicketDisputeController@tickets');
        Route::get('/disputes', 'Api\User\TicketDisputeController@disputes');
        Route::post('/ticket-dispute/store', 'Api\User\TicketDisputeController@store');
        Route::get('/ticket-dispute/{id}/delete', 'Api\User\TicketDisputeController@delete');
        Route::post('/ticket-dispute/message/store', 'Api\User\TicketDisputeController@messageStore');

        // --------------------- TICKET & DISPUTE ENDS ---------------------

        // ---------------------MESSAGE CONTROLLER ---------------------

        Route::post('/message/store', 'Api\User\MessageController@usercontact');
        Route::post('/message/post', 'Api\User\MessageController@postmessage');
        Route::get('/messages', 'Api\User\MessageController@messages');
        Route::get('/message/{id}/delete', 'Api\User\MessageController@messagedelete');

        // ---------------------MESSAGE CONTROLLER ENDS ---------------------


        // ---------------------CATALOG ITEM CONTROLLER ---------------------

        Route::post('/reviewsubmit', 'Api\User\CatalogItemController@reviewsubmit');
        Route::post('/commentstore', 'Api\User\CatalogItemController@commentstore');
        Route::post('/commentupdate', 'Api\User\CatalogItemController@commentupdate');
        Route::post('/replystore', 'Api\User\CatalogItemController@replystore');
        Route::post('/replyupdate', 'Api\User\CatalogItemController@replyupdate');
        Route::post('/reportstore', 'Api\User\CatalogItemController@reportstore');
        Route::get('/comment/{id}/delete', 'Api\User\CatalogItemController@commentdelete');
        Route::get('/reply/{id}/delete', 'Api\User\CatalogItemController@replydelete');

        // ---------------------CATALOG ITEM CONTROLLER ENDS ---------------------

        // ---------------------PURCHASE CONTROLLER ---------------------

        Route::get('/purchases', 'Api\User\PurchaseController@purchases')->name('purchases');
        Route::get('/purchase/{id}/details', 'Api\User\PurchaseController@purchase')->name('purchase');
        Route::post('/update/transactionid', 'Api\User\PurchaseController@updateTransaction');

        // ---------------------PURCHASE CONTROLLER ENDS ---------------------

        // ---------------------WITHDRAW CONTROLLER ---------------------

        Route::get('/withdraws', 'Api\User\WithdrawController@index');
        Route::get('/withdraw/methods/field', 'Api\User\WithdrawController@methods_field');
        Route::post('/withdraw/create', 'Api\User\WithdrawController@store');

        // ---------------------WITHDRAW CONTROLLER ENDS ---------------------
        
        
        // ---------------------FAVORITE CONTROLLER ---------------------

        Route::get('/favorites','Api\User\FavoriteController@favorites');
        Route::post('/favorite/add','Api\User\FavoriteController@add');
        Route::get('/favorite/remove/{id}','Api\User\FavoriteController@remove');

        // ---------------------FAVORITE CONTROLLER ENDS ---------------------        
        
        
         // ---------------------REWORD CONTROLLER ---------------------  
       Route::get('/reword/get', 'Api\User\WithdrawController@getReword');
       Route::post('/reword/store', 'Api\User\WithdrawController@convertSubmit');
          
     // ---------------------REWORD CONTROLLER ---------------------  
        // ---------------------PACKAGE CONTROLLER ---------------------

        Route::get('/packages', 'Api\User\PackageController@packages');
        Route::get('/package/details', 'Api\User\PackageController@packageDetails');
        Route::post('/package/store', 'Api\User\PackageController@store');

        // ---------------------PACKAGE CONTROLLER ENDS ---------------------

          // ---------------------DEPOSIT CONTROLLER ---------------------

          Route::get('/deposits', 'Api\User\DepositController@deposits');
          Route::post('/deposit/store', 'Api\User\DepositController@store');
          Route::get('/transactions', 'Api\User\DepositController@transactions');
          Route::get('/transaction/details', 'Api\User\DepositController@transactionDetails');
  
          // ---------------------DEPOSIT CONTROLLER ENDS ---------------------
  

    });

});


Route::group(['prefix' => 'front'], function () {

    //------------ Frontend Controller ------------
    Route::get('/section-customization', 'Api\Front\FrontendController@section_customization');
    Route::get('/sliders', 'Api\Front\FrontendController@sliders');
    Route::get('/default/language', 'Api\Front\FrontendController@defaultLanguage');
    Route::get('/language/{id}', 'Api\Front\FrontendController@language');
    Route::get('/languages', 'Api\Front\FrontendController@languages');
    Route::get('/default/currency', 'Api\Front\FrontendController@defaultCurrency');
    Route::get('/currency/{id}', 'Api\Front\FrontendController@currency');
    Route::get('/currencies', 'Api\Front\FrontendController@currencies');
    Route::get('/deal-of-day', 'Api\Front\FrontendController@deal');
    Route::get('/arrival', 'Api\Front\FrontendController@arrival');
    Route::get('/arrival', 'Api\Front\FrontendController@arrival');

    Route::get('/services', 'Api\Front\FrontendController@services');
    Route::get('/banners', 'Api\Front\FrontendController@banners');
    Route::get('/brands', 'Api\Front\FrontendController@brands');
    Route::get('/catalog-items', 'Api\Front\FrontendController@catalogItems');
    Route::get('/vendor/catalog-items/{id}', 'Api\Front\FrontendController@vendor_catalog_items');
    Route::get('/settings', 'Api\Front\FrontendController@settings');
    Route::get('/faqs', 'Api\Front\FrontendController@faqs');
    Route::get('/blogs', 'Api\Front\FrontendController@blogs');
    Route::get('/pages', 'Api\Front\FrontendController@pages');
    Route::get('/purchasetrack','Api\Front\FrontendController@purchasetrack');
    Route::post('/contactmail', 'Api\Front\FrontendController@contactmail');

    //------------ Frontend Controller Ends ------------

    //------------ Search Controller ------------

    Route::get('/search','Api\Front\SearchController@search');
    Route::get('/categories', 'Api\Front\SearchController@categories');
    Route::get('/category/catalog-item/search', 'Api\Front\SearchController@categoriesSearch');
    Route::get('{id}/category', 'Api\Front\SearchController@category');
    Route::get('/{id}/subcategories', 'Api\Front\SearchController@subcategories')->name('subcategories');
    Route::get('/{id}/childcategories', 'Api\Front\SearchController@childcategories')->name('childcategories');
    Route::get('/attributes/{id}', 'Api\Front\SearchController@attributes')->name('attibutes');
    Route::get('/attributeoptions/{id}', 'Api\Front\SearchController@attributeoptions')->name('attibute.options');

    //------------ Search Controller Ends ------------

    //------------ Catalog Item Controller ------------

    Route::get('/catalog-item/{id}/details', 'Api\Front\CatalogItemController@catalogItemDetails');
    Route::get('/catalog-item/{id}/catalog-reviews', 'Api\Front\CatalogItemController@catalogReviews');
    Route::get('/catalog-item/{id}/comments', 'Api\Front\CatalogItemController@comments');
    Route::get('/catalog-item/{id}/replies', 'Api\Front\CatalogItemController@replies');

    //------------ Catalog Item Controller Ends ------------

    //------------ Merchant Controller ------------

    Route::get('/store/{shop_name}','Api\Front\MerchantController@index')->name('api.front.merchant');
    Route::post('/store/contact','Api\Front\MerchantController@merchantcontact');

    //------------ Merchant Controller ------------

    //------------ Checkout Controller ------------

    Route::post('/checkout','Api\Front\CheckoutController@checkout');
   
    Route::get('/get-shipping-packaging','Api\Front\CheckoutController@getShippingPackaging');
    Route::get('/merchant/wise/shipping-packaging','Api\Front\CheckoutController@MerchantWisegetShippingPackaging');
    Route::get('/purchase/details','Api\Front\CheckoutController@purchaseDetails');
    Route::get('/get/discount-code','Api\Front\CheckoutController@getDiscountCode');
    Route::post('/checkout/update/{id}','Api\Front\CheckoutController@update');
    Route::get('/checkout/delete/{id}','Api\Front\CheckoutController@delete');
    Route::get('/get/countries','Api\Front\CheckoutController@countries');
    //------------ Checkout Controller ------------

});

Route::fallback(function () {
    return response()->json(['status' => false, 'data' => [], 'error' => ['message' => 'Not Found!']], 404);
});

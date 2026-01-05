<?php

// ************************************ OPERATOR SECTION **********************************************

use App\Livewire\Catlogs;
use App\Models\Token;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;
use Siberfx\LaravelTryoto\app\Http\Controllers\Api\TryOtoController;


use App\Http\Controllers\Api\CalloutController;
use App\Http\Controllers\Front\CatalogItemDetailsController;
use App\Http\Controllers\Front\SearchApiController;
use App\Http\Controllers\Front\VehicleSearchApiController;

// Search API Routes (AJAX-based)
Route::prefix('api/search')->group(function () {
    Route::get('/part', [SearchApiController::class, 'searchPart'])->name('api.search.part');
    Route::get('/vin', [SearchApiController::class, 'searchVin'])->name('api.search.vin');
    Route::post('/vin/select', [SearchApiController::class, 'selectVin'])->name('api.search.vin.select');
});

// Vehicle Search API Routes (AJAX-based)
Route::prefix('api/vehicle')->group(function () {
    Route::get('/suggestions', [VehicleSearchApiController::class, 'searchSuggestions'])->name('api.vehicle.suggestions');
    Route::get('/search', [VehicleSearchApiController::class, 'search'])->name('api.vehicle.search');
});



// Route::get('/refresh-stock/{token}', function ($token) {
//     abort_unless($token === env('REFRESH_TOKEN'), 403);

//     Artisan::call('stock:full-refresh');
//     $refreshOutput = Artisan::output();

//     Artisan::call('catalogItems:update-price');
//     $priceOutput = Artisan::output();

//     return response()->json([
//         'status' => 'success',
//         'refresh_output' => $refreshOutput,
//         'price_output'   => $priceOutput,
//     ]);
// });


// Route::get('/refresh-stock/{token}', function ($token) {
//     abort_unless($token === env('REFRESH_TOKEN'), 403);

//     $logs = [];

//     // تشغيل full-refresh
//     Artisan::call('stock:full-refresh');
//     $logs[] = Artisan::output();

//     // تشغيل تحديث الأسعار
//     Artisan::call('catalogItems:update-price');
//     $logs[] = Artisan::output();

//     // دمج المخرجات
//     $output = implode("\n\n", $logs);

//     // تنسيقات بالألوان
//     $styled = htmlspecialchars($output); // أمان ضد أي أكواد
//     $styled = preg_replace('/✔ (.*)/', '<span style="color:green;font-weight:bold">✔ $1</span>', $styled);
//     $styled = preg_replace('/❌ (.*)/', '<span style="color:red;font-weight:bold">❌ $1</span>', $styled);
//     $styled = preg_replace('/⚠ (.*)/', '<span style="color:orange;font-weight:bold">⚠ $1</span>', $styled);
//     $styled = preg_replace('/ℹ (.*)/', '<span style="color:gray">ℹ $1</span>', $styled);
//     $styled = preg_replace('/🎉 (.*)/', '<span style="color:blue;font-weight:bold">🎉 $1</span>', $styled);

//     return <<<HTML
//     <html>
//       <head>
//         <title>Stock Refresh Logs</title>
//         <meta charset="utf-8">
//         <style>
//           body { background:#f8f9fa; font-family:Arial, sans-serif; padding:20px; }
//           pre { background:#fff; padding:20px; border-radius:10px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
//         </style>
//       </head>
//       <body>
//         <h2>📋 Stock Refresh & Price Update Logs</h2>
//         <pre>{$styled}</pre>
//       </body>
//     </html>
//     HTML;
// });

// Route::get('/refresh-stock/{token}', function ($token) {
//     abort_unless($token === env('REFRESH_TOKEN'), 403);

//     $output = [];

//     // تنزيل + استيراد + تجميع
//     Artisan::call('stock:full-refresh');
//     $output[] = Artisan::output();

//     // تحديث الأسعار
//     Artisan::call('catalogItems:update-price');
//     $output[] = Artisan::output();

//     // نجمع كل المخرجات ونرجعها كـ نص
//     return "<pre>" . implode("\n\n", $output) . "</pre>";
// });

Route::get('/refresh-stock/{token}', function ($token) {
    abort_unless($token === env('REFRESH_TOKEN'), 403);

    $output = [];

    // تنزيل + استيراد + تجميع + تحديث مخزون وأسعار لبائع واحد (59) على فرع ATWJRY
    Artisan::call('stock:manage', [
        'action'    => 'full-refresh',
        '--user_id' => 59,
        '--margin'  => 1.3,
        '--branch'  => 'ATWJRY',
    ]);
    $output[] = Artisan::output();

    // عرض النتائج
    return "<pre>" . implode("\n\n", $output) . "</pre>";
});

Route::get('/checkout/quick', 'Front\QuickCheckoutController@quick')->name('front.checkout.quick');

Route::prefix('modal')->name('modal.')->group(function () {
    Route::get('/catalog-item/id/{catalogItem}',   [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.id');
    Route::get('/catalog-item/part_number/{part_number}',      [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.part_number');
    Route::get('/compatibility/{key}',    [CatalogItemDetailsController::class, 'compatibilityFragment'])->name('compatibility');
    Route::get('/alternative/{key}',      [CatalogItemDetailsController::class, 'alternativeFragment'])->name('alternative');
    Route::get('/quickview/{id}',         [CatalogItemDetailsController::class, 'quickFragment'])->name('quickview');
    Route::get('/catalog-item/{key}',          [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item');
});

// ✅ API Routes with Rate Limiting
Route::prefix('api')->middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('/callouts', [CalloutController::class, 'show'])->name('api.callouts.show');
    Route::get('/callouts/metadata', [CalloutController::class, 'metadata'])->name('api.callouts.metadata');
});





Route::get('/under-maintenance', 'Front\FrontendController@maintenance')->name('front-maintenance');

Route::prefix('operator')->group(function () {

    //------------ OPERATOR LOGIN SECTION ------------

    Route::get('/login', 'Auth\Operator\LoginController@showForm')->name('operator.login');
    Route::post('/login', 'Auth\Operator\LoginController@login')->name('operator.login.submit');
    Route::get('/logout', 'Auth\Operator\LoginController@logout')->name('operator.logout');

    //------------ OPERATOR LOGIN SECTION ENDS ------------

    //------------ OPERATOR FORGOT SECTION ------------

    Route::get('/forgot', 'Auth\Operator\ForgotController@showForm')->name('operator.forgot');
    Route::post('/forgot', 'Auth\Operator\ForgotController@forgot')->name('operator.forgot.submit');
    Route::get('/change-password/{token}', 'Auth\Operator\ForgotController@showChangePassForm')->name('operator.change.token');
    Route::post('/change-password', 'Auth\Operator\ForgotController@changepass')->name('operator.change.password');

    //------------ OPERATOR FORGOT SECTION ENDS ------------

    //------------ PROTECTED OPERATOR ROUTES (Require Authentication) ------------
    Route::middleware(['auth:operator'])->group(function () {

        //------------ OPERATOR NOTIFICATION SECTION ------------
        Route::get('/all/notf/count', 'Operator\NotificationController@all_notf_count')->name('all-notf-count');
        Route::get('/user/notf/show', 'Operator\NotificationController@user_notf_show')->name('user-notf-show');
        Route::get('/user/notf/clear', 'Operator\NotificationController@user_notf_clear')->name('user-notf-clear');
        Route::get('/purchase/notf/show', 'Operator\NotificationController@purchase_notf_show')->name('purchase-notf-show');
        Route::get('/purchase/notf/clear', 'Operator\NotificationController@purchase_notf_clear')->name('purchase-notf-clear');
        Route::get('/catalog-item/notf/show', 'Operator\NotificationController@catalog_item_notf_show')->name('catalog-item-notf-show');
        Route::get('/catalog-item/notf/clear', 'Operator\NotificationController@catalog_item_notf_clear')->name('catalog-item-notf-clear');
        Route::get('/conv/notf/show', 'Operator\NotificationController@conv_notf_show')->name('conv-notf-show');
        Route::get('/conv/notf/clear', 'Operator\NotificationController@conv_notf_clear')->name('conv-notf-clear');
        //------------ OPERATOR NOTIFICATION SECTION ENDS ------------

        //------------ OPERATOR DASHBOARD & PROFILE SECTION ------------
        Route::get('/', 'Operator\DashboardController@index')->name('operator.dashboard');
        Route::get('/profile', 'Operator\DashboardController@profile')->name('operator.profile');
        Route::post('/profile/update', 'Operator\DashboardController@profileupdate')->name('operator.profile.update');
        Route::get('/password', 'Operator\DashboardController@passwordreset')->name('operator.password');
        Route::post('/password/update', 'Operator\DashboardController@changepass')->name('operator.password.update');
        //------------ OPERATOR DASHBOARD & PROFILE SECTION ENDS ------------

        //------------ OPERATORPERFORMANCE MONITORING SECTION ------------
        Route::get('/performance', 'Operator\PerformanceController@index')->name('operator-performance');
        Route::get('/performance/slow-queries', 'Operator\PerformanceController@slowQueries')->name('operator-performance-slow-queries');
        Route::get('/performance/slow-requests', 'Operator\PerformanceController@slowRequests')->name('operator-performance-slow-requests');
        Route::get('/performance/repeated-queries', 'Operator\PerformanceController@repeatedQueries')->name('operator-performance-repeated-queries');
        Route::get('/performance/report', 'Operator\PerformanceController@downloadReport')->name('operator-performance-report');
        Route::get('/performance/api/summary', 'Operator\PerformanceController@apiSummary')->name('operator-performance-api-summary');
        Route::post('/performance/prune', 'Operator\PerformanceController@pruneOldEntries')->name('operator-performance-prune');
        //------------ OPERATORPERFORMANCE MONITORING SECTION ENDS ------------

        //------------ OPERATORAPI CREDENTIALS SECTION ------------
        Route::get('/credentials', 'Operator\ApiCredentialController@index')->name('operator.credentials.index');
        Route::get('/credentials/create', 'Operator\ApiCredentialController@create')->name('operator.credentials.create');
        Route::post('/credentials', 'Operator\ApiCredentialController@store')->name('operator.credentials.store');
        Route::get('/credentials/{id}/edit', 'Operator\ApiCredentialController@edit')->name('operator.credentials.edit');
        Route::put('/credentials/{id}', 'Operator\ApiCredentialController@update')->name('operator.credentials.update');
        Route::delete('/credentials/{id}', 'Operator\ApiCredentialController@destroy')->name('operator.credentials.destroy');
        Route::post('/credentials/{id}/toggle', 'Operator\ApiCredentialController@toggle')->name('operator.credentials.toggle');
        Route::post('/credentials/{id}/test', 'Operator\ApiCredentialController@test')->name('operator.credentials.test');
        //------------ OPERATORAPI CREDENTIALS SECTION ENDS ------------

        //------------ OPERATORMERCHANT CREDENTIALS SECTION ------------
        Route::get('/merchant-credentials', 'Operator\MerchantCredentialController@index')->name('operator.merchant-credentials.index');
        Route::get('/merchant-credentials/create', 'Operator\MerchantCredentialController@create')->name('operator.merchant-credentials.create');
        Route::post('/merchant-credentials', 'Operator\MerchantCredentialController@store')->name('operator.merchant-credentials.store');
        Route::get('/merchant-credentials/{id}/edit', 'Operator\MerchantCredentialController@edit')->name('operator.merchant-credentials.edit');
        Route::put('/merchant-credentials/{id}', 'Operator\MerchantCredentialController@update')->name('operator.merchant-credentials.update');
        Route::delete('/merchant-credentials/{id}', 'Operator\MerchantCredentialController@destroy')->name('operator.merchant-credentials.destroy');
        Route::post('/merchant-credentials/{id}/toggle', 'Operator\MerchantCredentialController@toggle')->name('operator.merchant-credentials.toggle');
        Route::post('/merchant-credentials/{id}/test', 'Operator\MerchantCredentialController@test')->name('operator.merchant-credentials.test');
        //------------ OPERATORMERCHANT CREDENTIALS SECTION ENDS ------------
    });

    //------------ OPERATORPURCHASE SECTION ------------

    Route::group(['middleware' => 'permissions:purchases'], function () {

        Route::get('/purchases/datatables/{slug}', 'Operator\PurchaseController@datatables')->name('operator-purchase-datatables'); //JSON REQUEST
        Route::get('/purchases', 'Operator\PurchaseController@purchases')->name('operator-purchases-all');
        Route::get('/purchase/edit/{id}', 'Operator\PurchaseController@edit')->name('operator-purchase-edit');
        Route::post('/purchase/update/{id}', 'Operator\PurchaseController@update')->name('operator-purchase-update');
        Route::get('/purchase/{id}/show', 'Operator\PurchaseController@show')->name('operator-purchase-show');
        Route::get('/purchase/{id}/invoice', 'Operator\PurchaseController@invoice')->name('operator-purchase-invoice');
        Route::get('/purchase/{id}/print', 'Operator\PurchaseController@printpage')->name('operator-purchase-print');
        Route::get('/purchase/{id1}/status/{status}', 'Operator\PurchaseController@status')->name('operator-purchase-status');
        Route::post('/purchase/email/', 'Operator\PurchaseController@emailsub')->name('operator-purchase-emailsub');
        Route::post('/purchase/{id}/license', 'Operator\PurchaseController@license')->name('operator-purchase-license');
        Route::post('/purchase/catalogItem-submit', 'Operator\PurchaseController@catalogItem_submit')->name('operator-purchase-catalogItem-submit');
        Route::get('/purchase/catalogItem-show/{id}', 'Operator\PurchaseController@catalogItem_show');
        Route::get('/purchase/addcart/{id}', 'Operator\PurchaseController@addcart');
        Route::get('/purchasecart/catalogItem-edit/{id}/{itemid}/{purchaseid}', 'Operator\PurchaseController@catalogItem_edit')->name('operator-purchase-catalogItem-edit');
        Route::get('/purchase/updatecart/{id}', 'Operator\PurchaseController@updatecart');
        Route::get('/purchasecart/catalogItem-delete/{id}/{purchaseid}', 'Operator\PurchaseController@catalogItem_delete')->name('operator-purchase-catalogItem-delete');
        // Purchase Tracking

        // CREATE PURCHASE

        Route::get('/purchase/catalog-item/datatables', 'Operator\PurchaseCreateController@datatables')->name('operator-purchase-catalog-item-datatables');
        Route::get('/purchase/create', 'Operator\PurchaseCreateController@create')->name('operator-purchase-create');
        Route::get('/purchase/catalog-item/add/{catalog_item_id}', 'Operator\PurchaseCreateController@addCatalogItem')->name('operator-purchase-catalog-item-add');
        Route::get('/purchase/catalog-item/add', 'Operator\PurchaseCreateController@purchaseStore')->name('operator.purchase.store.new');
        Route::get('/purchase/catalog-item/remove/{catalog_item_id}', 'Operator\PurchaseCreateController@removePurchaseCatalogItem')->name('operator.purchase.catalog-item.remove');
        Route::get('/purchase/create/catalog-item-show/{id}', 'Operator\PurchaseCreateController@catalog_item_show');
        Route::get('/purchase/create/addcart/{id}', 'Operator\PurchaseCreateController@addcart');
        Route::get('/purchase/remove/addcart/{id}', 'Operator\PurchaseCreateController@removeCart')->name('operator.purchase.remove.cart');
        Route::get('/purchase/create/user-address', 'Operator\PurchaseCreateController@userAddress');
        Route::post('/purchase/create/user-address', 'Operator\PurchaseCreateController@userAddressSubmit')->name('operator.purchase.create.user.address');
        Route::post('/purchase/create/purchase/view', 'Operator\PurchaseCreateController@viewCreatePurchase')->name('operator.purchase.create.view');
        Route::get('/purchase/create/purchase/submit', 'Operator\PurchaseCreateController@CreatePurchaseSubmit')->name('operator-purchase-create-submit');

        Route::get('/purchase/{id}/timeline', 'Operator\PurchaseTimelineController@index')->name('operator-purchase-timeline');
        Route::get('/purchase/{id}/timelineload', 'Operator\PurchaseTimelineController@load')->name('operator-purchase-timeline-load');
        Route::post('/purchase/timeline/store', 'Operator\PurchaseTimelineController@store')->name('operator-purchase-timeline-store');
        Route::get('/purchase/timeline/add', 'Operator\PurchaseTimelineController@add')->name('operator-purchase-timeline-add');
        Route::get('/purchase/timeline/edit/{id}', 'Operator\PurchaseTimelineController@edit')->name('operator-purchase-timeline-edit');
        Route::post('/purchase/timeline/update/{id}', 'Operator\PurchaseTimelineController@update')->name('operator-purchase-timeline-update');
        Route::delete('/purchase/timeline/delete/{id}', 'Operator\PurchaseTimelineController@delete')->name('operator-purchase-timeline-delete');

        // Purchase Tracking Ends

    });

    //------------ OPERATORPURCHASE SECTION ENDS------------

    //------------ OPERATORSHIPMENTS SECTION ------------

    Route::group(['middleware' => 'permissions:purchases'], function () {
        Route::get('/shipments', 'Operator\ShipmentController@index')->name('operator.shipments.index');
        Route::get('/shipments/show/{tracking}', 'Operator\ShipmentController@show')->name('operator.shipments.show');
        Route::get('/shipments/refresh/{tracking}', 'Operator\ShipmentController@refresh')->name('operator.shipments.refresh');
        Route::post('/shipments/cancel/{tracking}', 'Operator\ShipmentController@cancel')->name('operator.shipments.cancel');
        Route::get('/shipments/export', 'Operator\ShipmentController@export')->name('operator.shipments.export');
        Route::post('/shipments/bulk-refresh', 'Operator\ShipmentController@bulkRefresh')->name('operator.shipments.bulk-refresh');
        Route::get('/shipments/reports', 'Operator\ShipmentController@reports')->name('operator.shipments.reports');
    });

    //------------ OPERATORSHIPMENTS SECTION ENDS------------

    /////////////////////////////// ////////////////////////////////////////////

    // --------------- ADMIN COUNTRY & CITY SECTION (Protected) ---------------//
    Route::middleware(['auth:operator'])->group(function () {
        Route::get('/country/datatables', 'Operator\CountryController@datatables')->name('operator-country-datatables');
        Route::get('/manage/country', 'Operator\CountryController@manageCountry')->name('operator-country-index');
        Route::get('/manage/country/status/{id1}/{id2}', 'Operator\CountryController@status')->name('operator-country-status');
        Route::get('/country/delete/{id}', 'Operator\CountryController@delete')->name('operator-country-delete');
        Route::get('/country/tax/datatables', 'Operator\CountryController@taxDatatables')->name('operator-country-tax-datatables');
        Route::get('/manage/country/tax', 'Operator\CountryController@country_tax')->name('operator-country-tax');
        Route::get('/country/set-tax/{id}', 'Operator\CountryController@setTax')->name('operator-set-tax');
        Route::post('/country/set-tax/store/{id}', 'Operator\CountryController@updateTax')->name('operator-tax-update');

        Route::get('/city/datatables/{country}', 'Operator\CityController@datatables')->name('operator-city-datatables');
        Route::get('/manage/city/{country}', 'Operator\CityController@managecity')->name('operator-city-index');
        Route::get('/city/create/{country}', 'Operator\CityController@create')->name('operator-city-create');
        Route::post('/city/store/{country}', 'Operator\CityController@store')->name('operator-city-store');
        Route::get('/city/status/{id1}/{id2}', 'Operator\CityController@status')->name('operator-city-status');
        Route::get('/city/edit/{id}', 'Operator\CityController@edit')->name('operator-city-edit');
        Route::post('/city/update/{id}', 'Operator\CityController@update')->name('operator-city-update');
        Route::delete('/city/delete/{id}', 'Operator\CityController@delete')->name('operator-city-delete');
    });
    // --------------- ADMIN COUNTRY & CITY SECTION ENDS ---------------//

    //------------ OPERATORCATEGORY SECTION ENDS------------

    Route::group(['middleware' => 'permissions:earning'], function () {

        // -------------------------- Admin Total Income Route --------------------------//
        Route::get('tax/calculate', 'Operator\IncomeController@taxCalculate')->name('operator-tax-calculate-income');
        Route::get('withdraw/earning', 'Operator\IncomeController@withdrawIncome')->name('operator-withdraw-income');
        Route::get('commission/earning', 'Operator\IncomeController@commissionIncome')->name('operator-commission-income');
        // -------------------------- Admin Total Income Route --------------------------//
    });

    /////////////////////////////// ////////////////////////////////////////////

    // Note: Old Category/Subcategory/Childcategory and Attribute routes removed - now using TreeCategories

    //------------ OPERATORCATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:catalog_items'], function () {
        Route::get('/catalog-items/datatables', 'Operator\CatalogItemController@datatables')->name('operator-catalog-item-datatables');
        Route::get('/catalog-items', 'Operator\CatalogItemController@index')->name('operator-catalog-item-index');
        Route::post('/catalog-items/upload/update/{id}', 'Operator\CatalogItemController@uploadUpdate')->name('operator-catalog-item-upload-update');
        Route::get('/catalog-items/deactive', 'Operator\CatalogItemController@deactive')->name('operator-catalog-item-deactive');
        Route::get('/catalog-items/catalogs/datatables', 'Operator\CatalogItemController@catalogdatatables')->name('operator-catalog-item-catalog-datatables');
        Route::get('/catalog-items/catalogs/', 'Operator\CatalogItemController@catalogItemsCatalog')->name('operator-catalog-item-catalog-index');

        // CREATE SECTION
        Route::get('/catalog-items/types', 'Operator\CatalogItemController@types')->name('operator-catalog-item-types');
        Route::get('/catalog-items/{slug}/create', 'Operator\CatalogItemController@create')->name('operator-catalog-item-create');
        Route::post('/catalog-items/store', 'Operator\CatalogItemController@store')->name('operator-catalog-item-store');
        Route::get('/getspecs', 'Operator\CatalogItemController@getSpecs')->name('operator-catalog-item-getspecs');
        Route::get('/get/crosscatalogitem/{catid}', 'Operator\CatalogItemController@getCrossCatalogItem');

        // EDIT SECTION
        Route::get('/catalog-items/edit/{merchantItemId}', 'Operator\CatalogItemController@edit')->name('operator-catalog-item-edit');
        Route::post('/catalog-items/edit/{merchantItemId}', 'Operator\CatalogItemController@update')->name('operator-catalog-item-update');

        // DELETE SECTION
        Route::delete('/catalog-items/delete/{id}', 'Operator\CatalogItemController@destroy')->name('operator-catalog-item-delete');

        Route::get('/catalog-items/catalog/{id1}/{id2}', 'Operator\CatalogItemController@catalog')->name('operator-catalog-item-catalog');
        Route::get('/catalog-items/feature/{id}', 'Operator\CatalogItemController@feature')->name('operator-catalog-item-feature');
        Route::post('/catalog-items/feature/{id}', 'Operator\CatalogItemController@featuresubmit')->name('operator-catalog-item-feature.store');
        Route::get('/catalog-items/status/{id1}/{id2}', 'Operator\CatalogItemController@status')->name('operator-catalog-item-status');
        Route::get('/merchant-items/status/{id}/{status}', 'Operator\CatalogItemController@merchantItemStatus')->name('operator-merchant-item-status');
        Route::get('/catalog-items/settings', 'Operator\CatalogItemController@catalogItemSettings')->name('operator-gs-catalog-item-settings');
        Route::post('/catalog-items/settings/update', 'Operator\CatalogItemController@settingUpdate')->name('operator-gs-catalog-item-settings-update');
    });

    //------------ OPERATORCATALOG ITEM SECTION ENDS------------

    //------------ OPERATORAFFILIATE CATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:affilate_catalog_items'], function () {
        Route::get('/catalog-items/import/create', 'Operator\ImportController@createImport')->name('operator-import-create');
        Route::get('/catalog-items/import/edit/{id}', 'Operator\ImportController@edit')->name('operator-import-edit');
        Route::get('/catalog-items/import/datatables', 'Operator\ImportController@datatables')->name('operator-import-datatables');
        Route::get('/catalog-items/import/index', 'Operator\ImportController@index')->name('operator-import-index');
        Route::post('/catalog-items/import/store', 'Operator\ImportController@store')->name('operator-import-store');
        Route::post('/catalog-items/import/update/{id}', 'Operator\ImportController@update')->name('operator-import-update');
        Route::delete('/affiliate/catalog-items/delete/{id}', 'Operator\CatalogItemController@destroy')->name('operator-affiliate-catalog-item-delete');
    });

    //------------ OPERATORAFFILIATE CATALOG ITEM SECTION ENDS ------------

    //------------ OPERATORCSV IMPORT SECTION ------------

    Route::group(['middleware' => 'permissions:bulk_catalog_item_upload'], function () {
        Route::get('/catalog-items/import', 'Operator\CatalogItemController@import')->name('operator-catalog-item-import');
        Route::post('/catalog-items/import-submit', 'Operator\CatalogItemController@importSubmit')->name('operator-catalog-item-importsubmit');
    });

    //------------ OPERATORCSV IMPORT SECTION ENDS ------------

    //------------ OPERATORCATALOGITEM DISCUSSION SECTION ------------

    Route::group(['middleware' => 'permissions:catalogItem_discussion'], function () {

        // CATALOG REVIEW SECTION ------------

        Route::get('/catalog-reviews/datatables', 'Operator\CatalogReviewController@datatables')->name('operator-catalog-review-datatables'); //JSON REQUEST
        Route::get('/catalog-reviews', 'Operator\CatalogReviewController@index')->name('operator-catalog-review-index');
        Route::delete('/catalog-reviews/delete/{id}', 'Operator\CatalogReviewController@destroy')->name('operator-catalog-review-delete');
        Route::get('/catalog-reviews/show/{id}', 'Operator\CatalogReviewController@show')->name('operator-catalog-review-show');

        // CATALOG REVIEW SECTION ENDS------------

        // BUYER NOTE SECTION ------------

        Route::get('/buyer-notes/datatables', 'Operator\BuyerNoteController@datatables')->name('operator-buyer-note-datatables'); //JSON REQUEST
        Route::get('/buyer-notes', 'Operator\BuyerNoteController@index')->name('operator-buyer-note-index');
        Route::delete('/buyer-notes/delete/{id}', 'Operator\BuyerNoteController@destroy')->name('operator-buyer-note-delete');
        Route::get('/buyer-notes/show/{id}', 'Operator\BuyerNoteController@show')->name('operator-buyer-note-show');

        // BUYER NOTE SECTION ENDS ------------

        // ABUSE FLAG SECTION ------------

        Route::get('/abuse-flags/datatables', 'Operator\AbuseFlagController@datatables')->name('operator-abuse-flag-datatables'); //JSON REQUEST
        Route::get('/abuse-flags', 'Operator\AbuseFlagController@index')->name('operator-abuse-flag-index');
        Route::delete('/abuse-flags/delete/{id}', 'Operator\AbuseFlagController@destroy')->name('operator-abuse-flag-delete');
        Route::get('/abuse-flags/show/{id}', 'Operator\AbuseFlagController@show')->name('operator-abuse-flag-show');

        // ABUSE FLAG SECTION ENDS ------------

    });

    //------------ OPERATORPRODUCT DISCUSSION SECTION ENDS ------------

    //------------ OPERATORDISCOUNT CODE SECTION ------------

    Route::group(['middleware' => 'permissions:set_discount_codes'], function () {

        Route::get('/discount-code/datatables', 'Operator\DiscountCodeController@datatables')->name('operator-discount-code-datatables'); //JSON REQUEST
        Route::get('/discount-code', 'Operator\DiscountCodeController@index')->name('operator-discount-code-index');
        Route::get('/discount-code/create', 'Operator\DiscountCodeController@create')->name('operator-discount-code-create');
        Route::post('/discount-code/create', 'Operator\DiscountCodeController@store')->name('operator-discount-code-store');
        Route::get('/discount-code/edit/{id}', 'Operator\DiscountCodeController@edit')->name('operator-discount-code-edit');
        Route::post('/discount-code/edit/{id}', 'Operator\DiscountCodeController@update')->name('operator-discount-code-update');
        Route::delete('/discount-code/delete/{id}', 'Operator\DiscountCodeController@destroy')->name('operator-discount-code-delete');
        Route::get('/discount-code/status/{id1}/{id2}', 'Operator\DiscountCodeController@status')->name('operator-discount-code-status');
    });

    //------------ OPERATORDISCOUNT CODE SECTION ENDS------------

    //------------ OPERATORUSER SECTION ------------

    Route::group(['middleware' => 'permissions:customers'], function () {

        Route::get('/users/datatables', 'Operator\UserController@datatables')->name('operator-user-datatables'); //JSON REQUEST
        Route::get('/users', 'Operator\UserController@index')->name('operator-user-index');
        Route::get('/users/create', 'Operator\UserController@create')->name('operator-user-create');
        Route::post('/users/store', 'Operator\UserController@store')->name('operator-user-store');
        Route::get('/users/edit/{id}', 'Operator\UserController@edit')->name('operator-user-edit');
        Route::post('/users/edit/{id}', 'Operator\UserController@update')->name('operator-user-update');
        Route::delete('/users/delete/{id}', 'Operator\UserController@destroy')->name('operator-user-delete');
        Route::get('/user/{id}/show', 'Operator\UserController@show')->name('operator-user-show');
        Route::get('/users/ban/{id1}/{id2}', 'Operator\UserController@ban')->name('operator-user-ban');
        Route::get('/user/default/image', 'Operator\MuaadhSettingController@user_image')->name('operator-user-image');
        Route::get('/users/top-up/{id}', 'Operator\UserController@topUp')->name('operator-user-top-up');
        Route::post('/user/top-up/{id}', 'Operator\UserController@topUpUpdate')->name('operator-user-top-up-update');
        Route::get('/users/merchant/{id}', 'Operator\UserController@merchant')->name('operator-user-merchant');
        Route::post('/user/merchant/{id}', 'Operator\UserController@setMerchant')->name('operator-user-merchant-update');

        //USER WITHDRAW SECTION

        Route::get('/users/withdraws/datatables', 'Operator\UserController@withdrawdatatables')->name('operator-withdraw-datatables'); //JSON REQUEST
        Route::get('/users/withdraws', 'Operator\UserController@withdraws')->name('operator-withdraw-index');
        Route::get('/user/withdraw/{id}/show', 'Operator\UserController@withdrawdetails')->name('operator-withdraw-show');
        Route::get('/users/withdraws/accept/{id}', 'Operator\UserController@accept')->name('operator-withdraw-accept');
        Route::get('/user/withdraws/reject/{id}', 'Operator\UserController@reject')->name('operator-withdraw-reject');

        // WITHDRAW SECTION ENDS

        //COURIER WITHDRAW SECTION

        Route::get('/courier/withdraws/datatables', 'Operator\CourierController@withdrawdatatables')->name('operator-withdraw-courier-datatables'); //JSON REQUEST
        Route::get('/courier/withdraws', 'Operator\CourierController@withdraws')->name('operator-withdraw-courier-index');
        Route::get('/courier/withdraw/show/{id}', 'Operator\CourierController@withdrawdetails')->name('operator-withdraw-courier-show');
        Route::get('/courier/withdraw/accept/{id}', 'Operator\CourierController@accept')->name('operator-withdraw-courier-accept');
        Route::get('/courier/withdraw/reject/{id}', 'Operator\CourierController@reject')->name('operator-withdraw-courier-reject');

        // WITHDRAW SECTION ENDS

    });

    Route::group(['middleware' => 'permissions:couriers'], function () {

        Route::get('/couriers/datatables', 'Operator\CourierController@datatables')->name('operator-courier-datatables'); //JSON REQUEST
        Route::get('/couriers', 'Operator\CourierController@index')->name('operator-courier-index');

        Route::delete('/couriers/delete/{id}', 'Operator\CourierController@destroy')->name('operator-courier-delete');
        Route::get('/courier/{id}/show', 'Operator\CourierController@show')->name('operator-courier-show');
        Route::get('/couriers/ban/{id1}/{id2}', 'Operator\CourierController@ban')->name('operator-courier-ban');
        Route::get('/courier/default/image', 'Operator\MuaadhSettingController@courier_image')->name('operator-courier-image');

        // WITHDRAW SECTION

        Route::get('/couriers/withdraws/datatables', 'Operator\CourierController@withdrawdatatables')->name('operator-courier-withdraw-datatables'); //JSON REQUEST
        Route::get('/couriers/withdraws', 'Operator\CourierController@withdraws')->name('operator-courier-withdraw-index');
        Route::get('/courier/withdraw/{id}/show', 'Operator\CourierController@withdrawdetails')->name('operator-courier-withdraw-show');
        Route::get('/couriers/withdraws/accept/{id}', 'Operator\CourierController@accept')->name('operator-courier-withdraw-accept');
        Route::get('/courier/withdraws/reject/{id}', 'Operator\CourierController@reject')->name('operator-courier-withdraw-reject');

        // WITHDRAW SECTION ENDS

    });

    //------------ OPERATORUSER TOP UP & TRANSACTION SECTION ------------

    Route::group(['middleware' => 'permissions:customer_top_ups'], function () {

        Route::get('/users/top-up/datatables/{status}', 'Operator\UserTopUpController@datatables')->name('operator-user-top-up-datatables'); //JSON REQUEST
        Route::get('/users/top-ups/{slug}', 'Operator\UserTopUpController@topUps')->name('operator-user-top-ups');
        Route::get('/users/top-ups/status/{id1}/{id2}', 'Operator\UserTopUpController@status')->name('operator-user-top-up-status');
        Route::get('/wallet-logs/datatables', 'Operator\WalletLogController@transdatatables')->name('operator-wallet-log-datatables'); //JSON REQUEST
        Route::get('/wallet-logs', 'Operator\WalletLogController@index')->name('operator-wallet-log-index');
        Route::get('/wallet-logs/{id}/show', 'Operator\WalletLogController@transhow')->name('operator-wallet-log-show');
    });

    //------------ OPERATORUSER TOP-UP & TRANSACTION SECTION ------------

    //------------ OPERATORMERCHANT SECTION ------------

    Route::group(['middleware' => 'permissions:vendors'], function () {

        Route::get('/merchants/datatables', 'Operator\MerchantController@datatables')->name('operator-merchant-datatables');
        Route::get('/merchants', 'Operator\MerchantController@index')->name('operator-merchant-index');

        Route::get('/merchants/{id}/show', 'Operator\MerchantController@show')->name('operator-merchant-show');
        Route::get('/merchants/secret/login/{id}', 'Operator\MerchantController@secret')->name('operator-merchant-secret');
        Route::get('/merchant/edit/{id}', 'Operator\MerchantController@edit')->name('operator-merchant-edit');
        Route::post('/merchant/edit/{id}', 'Operator\MerchantController@update')->name('operator-merchant-update');

        Route::get('/merchant/verify/{id}', 'Operator\MerchantController@verify')->name('operator-merchant-verify');
        Route::post('/merchant/verify/{id}', 'Operator\MerchantController@verifySubmit')->name('operator-merchant-verify-submit');

        Route::get('/merchant/color', 'Operator\MuaadhSettingController@merchant_color')->name('operator-merchant-color');
        Route::get('/merchants/status/{id1}/{id2}', 'Operator\MerchantController@status')->name('operator-merchant-st');
        Route::delete('/merchants/delete/{id}', 'Operator\MerchantController@destroy')->name('operator-merchant-delete');
        Route::get('/merchant/commission/collect/{id}', 'Operator\MerchantController@commissionCollect')->name('operator-merchant-commission-collect');

        Route::get('/merchants/withdraws/datatables', 'Operator\MerchantController@withdrawdatatables')->name('operator-merchant-withdraw-datatables'); //JSON REQUEST
        Route::get('/merchants/withdraws', 'Operator\MerchantController@withdraws')->name('operator-merchant-withdraw-index');
        Route::get('/merchants/withdraw/{id}/show', 'Operator\MerchantController@withdrawdetails')->name('operator-merchant-withdraw-show');
        Route::get('/merchants/withdraws/accept/{id}', 'Operator\MerchantController@accept')->name('operator-merchant-withdraw-accept');
        Route::get('/merchants/withdraws/reject/{id}', 'Operator\MerchantController@reject')->name('operator-merchant-withdraw-reject');
    });

    //------------ OPERATORMERCHANT SECTION ENDS ------------

    //------------ MERCHANT COMMISSION SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_membership_plans'], function () {
        Route::get('/merchant-commissions/datatables', 'Operator\MerchantCommissionController@datatables')->name('operator-merchant-commission-datatables');
        Route::get('/merchant-commissions', 'Operator\MerchantCommissionController@index')->name('operator-merchant-commission-index');
        Route::get('/merchant-commissions/edit/{id}', 'Operator\MerchantCommissionController@edit')->name('operator-merchant-commission-edit');
        Route::post('/merchant-commissions/update/{id}', 'Operator\MerchantCommissionController@update')->name('operator-merchant-commission-update');
        Route::post('/merchant-commissions/bulk-update', 'Operator\MerchantCommissionController@bulkUpdate')->name('operator-merchant-commission-bulk-update');
    });

    //------------ MERCHANT COMMISSION SECTION ENDS ------------

    //------------ OPERATORVENDOR VERIFICATION SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_verifications'], function () {

        Route::get('/verificatons/datatables/{status}', 'Operator\VerificationController@datatables')->name('operator-vr-datatables');
        Route::get('/verificatons/{slug}', 'Operator\VerificationController@verificatons')->name('operator-vr-index');
        Route::get('/verificatons/show/attachment', 'Operator\VerificationController@show')->name('operator-vr-show');
        Route::get('/verificatons/edit/{id}', 'Operator\VerificationController@edit')->name('operator-vr-edit');
        Route::post('/verificatons/edit/{id}', 'Operator\VerificationController@update')->name('operator-vr-update');
        Route::get('/verificatons/status/{id1}/{id2}', 'Operator\VerificationController@status')->name('operator-vr-st');
        Route::delete('/verificatons/delete/{id}', 'Operator\VerificationController@destroy')->name('operator-vr-delete');
    });

    //------------ OPERATORVENDOR VERIFICATION SECTION ENDS ------------

    //------------ OPERATORSUPPORT TICKET SECTION ------------

    Route::group(['middleware' => 'permissions:messages'], function () {

        Route::get('/support-tickets/datatables/{type}', 'Operator\SupportTicketController@datatables')->name('operator-support-ticket-datatables');
        Route::get('/tickets', 'Operator\SupportTicketController@index')->name('operator-support-ticket-index');
        Route::get('/disputes', 'Operator\SupportTicketController@dispute')->name('operator-support-ticket-dispute');
        Route::get('/support-ticket/{id}', 'Operator\SupportTicketController@message')->name('operator-support-ticket-show');
        Route::get('/support-ticket/load/{id}', 'Operator\SupportTicketController@messageshow')->name('operator-support-ticket-load');
        Route::post('/support-ticket/post', 'Operator\SupportTicketController@postmessage')->name('operator-support-ticket-store');
        Route::delete('/support-ticket/{id}/delete', 'Operator\SupportTicketController@messagedelete')->name('operator-support-ticket-delete');
        Route::post('/user/send/support-ticket/admin', 'Operator\SupportTicketController@usercontact')->name('operator-send-support-ticket');
    });

    //------------ OPERATORSUPPORT TICKET SECTION ENDS ------------

    //------------ OPERATORPUBLICATION SECTION ------------

    Route::group(['middleware' => 'permissions:publication'], function () {

        Route::get('/publication/datatables', 'Operator\PublicationController@datatables')->name('operator-publication-datatables'); //JSON REQUEST
        Route::get('/publication', 'Operator\PublicationController@index')->name('operator-publication-index');
        Route::get('/publication/create', 'Operator\PublicationController@create')->name('operator-publication-create');
        Route::post('/publication/create', 'Operator\PublicationController@store')->name('operator-publication-store');
        Route::get('/publication/edit/{id}', 'Operator\PublicationController@edit')->name('operator-publication-edit');
        Route::post('/publication/edit/{id}', 'Operator\PublicationController@update')->name('operator-publication-update');
        Route::delete('/publication/delete/{id}', 'Operator\PublicationController@destroy')->name('operator-publication-delete');

        Route::get('/article-type/datatables', 'Operator\ArticleTypeController@datatables')->name('operator-article-type-datatables'); //JSON REQUEST
        Route::get('/article-type', 'Operator\ArticleTypeController@index')->name('operator-article-type-index');
        Route::get('/article-type/create', 'Operator\ArticleTypeController@create')->name('operator-article-type-create');
        Route::post('/article-type/create', 'Operator\ArticleTypeController@store')->name('operator-article-type-store');
        Route::get('/article-type/edit/{id}', 'Operator\ArticleTypeController@edit')->name('operator-article-type-edit');
        Route::post('/article-type/edit/{id}', 'Operator\ArticleTypeController@update')->name('operator-article-type-update');
        Route::delete('/article-type/delete/{id}', 'Operator\ArticleTypeController@destroy')->name('operator-article-type-delete');

        Route::get('/publication/publication-settings', 'Operator\PublicationController@settings')->name('operator-gs-publication-settings');
    });

    //------------ OPERATORPUBLICATION SECTION ENDS ------------

    //------------ OPERATORGENERAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:muaadh_settings'], function () {

        Route::get('/general-settings/logo', 'Operator\MuaadhSettingController@logo')->name('operator-gs-logo');
        Route::get('/general-settings/favicon', 'Operator\MuaadhSettingController@favicon')->name('operator-gs-fav');
        Route::get('/general-settings/loader', 'Operator\MuaadhSettingController@loader')->name('operator-gs-load');
        Route::get('/general-settings/contents', 'Operator\MuaadhSettingController@websitecontent')->name('operator-gs-contents');
        Route::get('/general-settings/theme-colors', 'Operator\MuaadhSettingController@themeColors')->name('operator-theme-colors');
        Route::post('/general-settings/theme-colors/update', 'Operator\MuaadhSettingController@updateThemeColors')->name('operator-theme-colors-update');
        Route::get('/general-settings/affilate', 'Operator\MuaadhSettingController@affilate')->name('operator-gs-affilate');
        Route::get('/general-settings/error-banner', 'Operator\MuaadhSettingController@error_banner')->name('operator-gs-error-banner');
        Route::get('/general-settings/popup', 'Operator\MuaadhSettingController@popup')->name('operator-gs-popup');
        // Breadcrumb banner removed - using modern minimal design
        Route::get('/general-settings/maintenance', 'Operator\MuaadhSettingController@maintain')->name('operator-gs-maintenance');

        // Deal Of The Day

        //------------ OPERATORPICKUP LOACTION ------------

        Route::get('/pickup/datatables', 'Operator\PickupController@datatables')->name('operator-pick-datatables'); //JSON REQUEST
        Route::get('/pickup', 'Operator\PickupController@index')->name('operator-pick-index');
        Route::get('/pickup/create', 'Operator\PickupController@create')->name('operator-pick-create');
        Route::post('/pickup/create', 'Operator\PickupController@store')->name('operator-pick-store');
        Route::get('/pickup/edit/{id}', 'Operator\PickupController@edit')->name('operator-pick-edit');
        Route::post('/pickup/edit/{id}', 'Operator\PickupController@update')->name('operator-pick-update');
        Route::delete('/pickup/delete/{id}', 'Operator\PickupController@destroy')->name('operator-pick-delete');

        //------------ OPERATORPICKUP LOACTION ENDS ------------

        //------------ OPERATORSHIPPING ------------

        Route::get('/shipping/datatables', 'Operator\ShippingController@datatables')->name('operator-shipping-datatables');
        Route::get('/shipping', 'Operator\ShippingController@index')->name('operator-shipping-index');
        Route::get('/shipping/create', 'Operator\ShippingController@create')->name('operator-shipping-create');
        Route::post('/shipping/create', 'Operator\ShippingController@store')->name('operator-shipping-store');
        Route::get('/shipping/edit/{id}', 'Operator\ShippingController@edit')->name('operator-shipping-edit');
        Route::post('/shipping/edit/{id}', 'Operator\ShippingController@update')->name('operator-shipping-update');
        Route::delete('/shipping/delete/{id}', 'Operator\ShippingController@destroy')->name('operator-shipping-delete');

        //------------ OPERATORSHIPPING ENDS ------------

        //------------ OPERATORPACKAGE ------------

        Route::get('/package/datatables', 'Operator\PackageController@datatables')->name('operator-package-datatables');
        Route::get('/package', 'Operator\PackageController@index')->name('operator-package-index');
        Route::get('/package/create', 'Operator\PackageController@create')->name('operator-package-create');
        Route::post('/package/create', 'Operator\PackageController@store')->name('operator-package-store');
        Route::get('/package/edit/{id}', 'Operator\PackageController@edit')->name('operator-package-edit');
        Route::post('/package/edit/{id}', 'Operator\PackageController@update')->name('operator-package-update');
        Route::delete('/package/delete/{id}', 'Operator\PackageController@destroy')->name('operator-package-delete');

        //------------ OPERATORPACKAGE ENDS------------

    });

    //------------ OPERATORGENERAL SETTINGS SECTION ENDS ------------

    //------------ OPERATORHOME PAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:home_page_settings'], function () {

        Route::get('/home-page-settings', 'Operator\MuaadhSettingController@homepage')->name('operator-home-page-index');

        //------------ OPERATORSLIDER SECTION ------------

        Route::get('/slider/datatables', 'Operator\SliderController@datatables')->name('operator-sl-datatables'); //JSON REQUEST
        Route::get('/slider', 'Operator\SliderController@index')->name('operator-sl-index');
        Route::get('/slider/create', 'Operator\SliderController@create')->name('operator-sl-create');
        Route::post('/slider/create', 'Operator\SliderController@store')->name('operator-sl-store');
        Route::get('/slider/edit/{id}', 'Operator\SliderController@edit')->name('operator-sl-edit');
        Route::post('/slider/edit/{id}', 'Operator\SliderController@update')->name('operator-sl-update');
        Route::delete('/slider/delete/{id}', 'Operator\SliderController@destroy')->name('operator-sl-delete');

        //------------ OPERATORSLIDER SECTION ENDS ------------

        //------------ OPERATORHOME PAGE THEMES SECTION ------------

        Route::get('/home-themes', 'Operator\HomePageThemeController@index')->name('operator-homethemes-index');
        Route::get('/home-themes/create', 'Operator\HomePageThemeController@create')->name('operator-homethemes-create');
        Route::post('/home-themes/store', 'Operator\HomePageThemeController@store')->name('operator-homethemes-store');
        Route::get('/home-themes/edit/{id}', 'Operator\HomePageThemeController@edit')->name('operator-homethemes-edit');
        Route::put('/home-themes/update/{id}', 'Operator\HomePageThemeController@update')->name('operator-homethemes-update');
        Route::get('/home-themes/activate/{id}', 'Operator\HomePageThemeController@activate')->name('operator-homethemes-activate');
        Route::get('/home-themes/duplicate/{id}', 'Operator\HomePageThemeController@duplicate')->name('operator-homethemes-duplicate');
        Route::delete('/home-themes/delete/{id}', 'Operator\HomePageThemeController@destroy')->name('operator-homethemes-delete');

        //------------ OPERATORHOME PAGE THEMES SECTION ENDS ------------

        Route::get('/featured-promo/datatables', 'Operator\FeaturedPromoController@datatables')->name('operator-featured-promo-datatables');
        Route::get('/featured-promo', 'Operator\FeaturedPromoController@index')->name('operator-featured-promo-index');
        Route::get('/featured-promo/create', 'Operator\FeaturedPromoController@create')->name('operator-featured-promo-create');
        Route::post('/featured-promo/create', 'Operator\FeaturedPromoController@store')->name('operator-featured-promo-store');
        Route::get('/featured-promo/edit/{id}', 'Operator\FeaturedPromoController@edit')->name('operator-featured-promo-edit');
        Route::post('/featured-promo/edit/{id}', 'Operator\FeaturedPromoController@update')->name('operator-featured-promo-update');
        Route::delete('/featured-promo/delete/{id}', 'Operator\FeaturedPromoController@destroy')->name('operator-featured-promo-delete');
        Route::get('/country/status/{id1}/{id2}', 'Operator\FeaturedPromoController@status')->name('operator-featured-promo-status');

        //------------ OPERATORSERVICE SECTION ------------

        Route::get('/service/datatables', 'Operator\ServiceController@datatables')->name('operator-service-datatables'); //JSON REQUEST
        Route::get('/service', 'Operator\ServiceController@index')->name('operator-service-index');
        Route::get('/service/create', 'Operator\ServiceController@create')->name('operator-service-create');
        Route::post('/service/create', 'Operator\ServiceController@store')->name('operator-service-store');
        Route::get('/service/edit/{id}', 'Operator\ServiceController@edit')->name('operator-service-edit');
        Route::post('/service/edit/{id}', 'Operator\ServiceController@update')->name('operator-service-update');
        Route::delete('/service/delete/{id}', 'Operator\ServiceController@destroy')->name('operator-service-delete');

        //------------ OPERATORSERVICE SECTION ENDS ------------

        //------------ OPERATORANNOUNCEMENT SECTION ------------

        Route::get('/announcement/datatables/{type}', 'Operator\AnnouncementController@datatables')->name('operator-announcement-datatables'); //JSON REQUEST
        Route::get('large/announcement/', 'Operator\AnnouncementController@large')->name('operator-announcement-large');
        Route::get('large/announcement/create', 'Operator\AnnouncementController@largecreate')->name('operator-announcement-create-large');
        Route::post('/announcement/create', 'Operator\AnnouncementController@store')->name('operator-announcement-store');
        Route::get('/announcement/edit/{id}', 'Operator\AnnouncementController@edit')->name('operator-announcement-edit');
        Route::post('/announcement/edit/{id}', 'Operator\AnnouncementController@update')->name('operator-announcement-update');
        Route::delete('/announcement/delete/{id}', 'Operator\AnnouncementController@destroy')->name('operator-announcement-delete');

        //------------ OPERATORANNOUNCEMENT SECTION ENDS ------------

        //------------ OPERATORBRAND SECTION ------------

        Route::get('/brand/datatables', 'Operator\BrandController@datatables')->name('operator-brand-datatables');
        Route::get('/brand', 'Operator\BrandController@index')->name('operator-brand-index');
        Route::get('/brand/create', 'Operator\BrandController@create')->name('operator-brand-create');
        Route::post('/brand/create', 'Operator\BrandController@store')->name('operator-brand-store');
        Route::get('/brand/edit/{id}', 'Operator\BrandController@edit')->name('operator-brand-edit');
        Route::post('/brand/edit/{id}', 'Operator\BrandController@update')->name('operator-brand-update');
        Route::delete('/brand/delete/{id}', 'Operator\BrandController@destroy')->name('operator-brand-delete');

        //------------ OPERATORBRAND SECTION ENDS ------------

        //------------ OPERATORPAGE SETTINGS SECTION ------------

        Route::get('/frontend-setting/customize', 'Operator\FrontendSettingController@customize')->name('operator-fs-customize');
        Route::get('/frontend-setting/best-seller', 'Operator\FrontendSettingController@best_seller')->name('operator-fs-best-seller');
    });

    //------------ OPERATORHOME PAGE SETTINGS SECTION ENDS ------------

    Route::group(['middleware' => 'permissions:menu_page_settings'], function () {

        //------------ OPERATORMENU PAGE SETTINGS SECTION ------------

        //------------ OPERATORHELP ARTICLE SECTION ------------

        Route::get('/help-article/datatables', 'Operator\HelpArticleController@datatables')->name('operator-help-article-datatables'); //JSON REQUEST
        Route::get('/help-article', 'Operator\HelpArticleController@index')->name('operator-help-article-index');
        Route::get('/help-article/create', 'Operator\HelpArticleController@create')->name('operator-help-article-create');
        Route::post('/help-article/create', 'Operator\HelpArticleController@store')->name('operator-help-article-store');
        Route::get('/help-article/edit/{id}', 'Operator\HelpArticleController@edit')->name('operator-help-article-edit');
        Route::post('/help-article/update/{id}', 'Operator\HelpArticleController@update')->name('operator-help-article-update');
        Route::delete('/help-article/delete/{id}', 'Operator\HelpArticleController@destroy')->name('operator-help-article-delete');

        //------------ OPERATORHELP ARTICLE SECTION ENDS ------------

        //------------ OPERATORSTATIC CONTENT SECTION ------------

        Route::get('/static-content/datatables', 'Operator\StaticContentController@datatables')->name('operator-static-content-datatables'); //JSON REQUEST
        Route::get('/static-content', 'Operator\StaticContentController@index')->name('operator-static-content-index');
        Route::get('/static-content/create', 'Operator\StaticContentController@create')->name('operator-static-content-create');
        Route::post('/static-content/create', 'Operator\StaticContentController@store')->name('operator-static-content-store');
        Route::get('/static-content/edit/{id}', 'Operator\StaticContentController@edit')->name('operator-static-content-edit');
        Route::post('/static-content/update/{id}', 'Operator\StaticContentController@update')->name('operator-static-content-update');
        Route::delete('/static-content/delete/{id}', 'Operator\StaticContentController@destroy')->name('operator-static-content-delete');
        Route::get('/static-content/header/{id1}/{id2}', 'Operator\StaticContentController@header')->name('operator-static-content-header');
        Route::get('/static-content/footer/{id1}/{id2}', 'Operator\StaticContentController@footer')->name('operator-static-content-footer');
        Route::get('/page/banner', 'Operator\FrontendSettingController@page_banner')->name('operator-fs-page-banner');
        Route::get('/right/banner', 'Operator\FrontendSettingController@right_banner')->name('operator-fs-right-banner');
        Route::get('/menu/links', 'Operator\FrontendSettingController@menu_links')->name('operator-fs-menu-links');
        Route::get('/deal/of/day', 'Operator\FrontendSettingController@deal')->name('operator-fs-deal');
        Route::post('/deal/of/day/toggle', 'Operator\FrontendSettingController@toggleDeal')->name('operator-fs-deal-toggle');
        Route::get('/deal/of/day/search', 'Operator\FrontendSettingController@searchDealCatalogItems')->name('operator-fs-deal-search');
        Route::get('/deal/of/day/merchants', 'Operator\FrontendSettingController@getCatalogItemMerchants')->name('operator-fs-deal-merchants');

        // Best Sellers Management
        Route::get('/best-sellers', 'Operator\FrontendSettingController@bestSellers')->name('operator-fs-best-sellers');
        Route::post('/best-sellers/toggle', 'Operator\FrontendSettingController@toggleBestSellers')->name('operator-fs-best-sellers-toggle');
        Route::get('/best-sellers/search', 'Operator\FrontendSettingController@searchBestSellersCatalogItems')->name('operator-fs-best-sellers-search');
        Route::get('/best-sellers/merchants', 'Operator\FrontendSettingController@getBestSellersMerchants')->name('operator-fs-best-sellers-merchants');

        // Top Rated Management
        Route::get('/top-rated', 'Operator\FrontendSettingController@topRated')->name('operator-fs-top-rated');
        Route::post('/top-rated/toggle', 'Operator\FrontendSettingController@toggleTopRated')->name('operator-fs-top-rated-toggle');
        Route::get('/top-rated/search', 'Operator\FrontendSettingController@searchTopRated')->name('operator-fs-top-rated-search');
        Route::get('/top-rated/merchants', 'Operator\FrontendSettingController@getTopRatedMerchants')->name('operator-fs-top-rated-merchants');

        // Big Save Management
        Route::get('/big-save', 'Operator\FrontendSettingController@bigSave')->name('operator-fs-big-save');
        Route::post('/big-save/toggle', 'Operator\FrontendSettingController@toggleBigSave')->name('operator-fs-big-save-toggle');
        Route::get('/big-save/search', 'Operator\FrontendSettingController@searchBigSave')->name('operator-fs-big-save-search');
        Route::get('/big-save/merchants', 'Operator\FrontendSettingController@getBigSaveMerchants')->name('operator-fs-big-save-merchants');

        // Trending Management
        Route::get('/trending', 'Operator\FrontendSettingController@trending')->name('operator-fs-trending');
        Route::post('/trending/toggle', 'Operator\FrontendSettingController@toggleTrending')->name('operator-fs-trending-toggle');
        Route::get('/trending/search', 'Operator\FrontendSettingController@searchTrending')->name('operator-fs-trending-search');
        Route::get('/trending/merchants', 'Operator\FrontendSettingController@getTrendingMerchants')->name('operator-fs-trending-merchants');

        // Featured CatalogItems Management
        Route::get('/featured', 'Operator\FrontendSettingController@featured')->name('operator-fs-featured');
        Route::post('/featured/toggle', 'Operator\FrontendSettingController@toggleFeatured')->name('operator-fs-featured-toggle');
        Route::get('/featured/search', 'Operator\FrontendSettingController@searchFeatured')->name('operator-fs-featured-search');
        Route::get('/featured/merchants', 'Operator\FrontendSettingController@getFeaturedMerchants')->name('operator-fs-featured-merchants');
        //------------ OPERATORPAGE SECTION ENDS------------

        Route::get('/frontend-setting/contact', 'Operator\FrontendSettingController@contact')->name('operator-fs-contact');
        Route::post('/frontend-setting/update/all', 'Operator\FrontendSettingController@update')->name('operator-fs-update');
    });

    //------------ OPERATORMENU PAGE SETTINGS SECTION ENDS ------------

    //------------ OPERATOREMAIL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:email_settings'], function () {

        Route::get('/email-templates/datatables', 'Operator\EmailController@datatables')->name('operator-mail-datatables');
        Route::get('/email-templates', 'Operator\EmailController@index')->name('operator-mail-index');
        Route::get('/email-templates/{id}', 'Operator\EmailController@edit')->name('operator-mail-edit');
        Route::post('/email-templates/{id}', 'Operator\EmailController@update')->name('operator-mail-update');
        Route::get('/email-config', 'Operator\EmailController@config')->name('operator-mail-config');
        Route::get('/groupemail', 'Operator\EmailController@groupemail')->name('operator-group-show');
        Route::post('/groupemailpost', 'Operator\EmailController@groupemailpost')->name('operator-group-submit');
    });

    if(module("otp")){
        
    Route::group(['middleware' => 'permissions:otp_setting'], function () {
        Route::get('/opt/config', 'Operator\MuaadhSettingController@otpConfig')->name('operator-otp-config');
    });

    }

    //------------ OPERATOREMAIL SETTINGS SECTION ENDS ------------

    //------------ OPERATORPAYMENT SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:payment_settings'], function () {

        // Payment Informations

        Route::get('/payment-informations', 'Operator\MuaadhSettingController@paymentsinfo')->name('operator-gs-payments');

        // Merchant Payments

        Route::get('/merchant-payment/datatables', 'Operator\MerchantPaymentController@datatables')->name('operator-merchant-payment-datatables'); //JSON REQUEST
        Route::get('/merchant-payment', 'Operator\MerchantPaymentController@index')->name('operator-merchant-payment-index');
        Route::get('/merchant-payment/create', 'Operator\MerchantPaymentController@create')->name('operator-merchant-payment-create');
        Route::post('/merchant-payment/create', 'Operator\MerchantPaymentController@store')->name('operator-merchant-payment-store');
        Route::get('/merchant-payment/edit/{id}', 'Operator\MerchantPaymentController@edit')->name('operator-merchant-payment-edit');
        Route::post('/merchant-payment/update/{id}', 'Operator\MerchantPaymentController@update')->name('operator-merchant-payment-update');
        Route::delete('/merchant-payment/delete/{id}', 'Operator\MerchantPaymentController@destroy')->name('operator-merchant-payment-delete');
        Route::get('/merchant-payment/status/{field}/{id1}/{id2}', 'Operator\MerchantPaymentController@status')->name('operator-merchant-payment-status');

        // Currency Settings

        // MULTIPLE CURRENCY

        Route::get('/currency/datatables', 'Operator\CurrencyController@datatables')->name('operator-currency-datatables'); //JSON REQUEST
        Route::get('/currency', 'Operator\CurrencyController@index')->name('operator-currency-index');
        Route::get('/currency/create', 'Operator\CurrencyController@create')->name('operator-currency-create');
        Route::post('/currency/create', 'Operator\CurrencyController@store')->name('operator-currency-store');
        Route::get('/currency/edit/{id}', 'Operator\CurrencyController@edit')->name('operator-currency-edit');
        Route::post('/currency/update/{id}', 'Operator\CurrencyController@update')->name('operator-currency-update');
        Route::delete('/currency/delete/{id}', 'Operator\CurrencyController@destroy')->name('operator-currency-delete');
        Route::get('/currency/status/{id1}/{id2}', 'Operator\CurrencyController@status')->name('operator-currency-status');

        // -------------------- Reward Section Route ---------------------//
        Route::get('rewards/datatables', 'Operator\RewardController@datatables')->name('operator-reward-datatables');
        Route::get('rewards', 'Operator\RewardController@index')->name('operator-reward-index');
        Route::get('/general-settings/reward/{status}', 'Operator\MuaadhSettingController@isreward')->name('operator-gs-is_reward');
        Route::post('reward/update/', 'Operator\RewardController@update')->name('operator-reward-update');
        Route::post('reward/information/update', 'Operator\RewardController@infoUpdate')->name('operator-reward-info-update');

        // -------------------- Reward Section Route ---------------------//

    });

    //------------ OPERATORPAYMENT SETTINGS SECTION ENDS------------

    //------------ OPERATORSOCIAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:social_settings'], function () {

        //------------ OPERATORSOCIAL LINK ------------

        Route::get('/social-link/datatables', 'Operator\SocialLinkController@datatables')->name('operator-sociallink-datatables'); //JSON REQUEST
        Route::get('/social-link', 'Operator\SocialLinkController@index')->name('operator-sociallink-index');
        Route::get('/social-link/create', 'Operator\SocialLinkController@create')->name('operator-sociallink-create');
        Route::post('/social-link/create', 'Operator\SocialLinkController@store')->name('operator-sociallink-store');
        Route::get('/social-link/edit/{id}', 'Operator\SocialLinkController@edit')->name('operator-sociallink-edit');
        Route::post('/social-link/edit/{id}', 'Operator\SocialLinkController@update')->name('operator-sociallink-update');
        Route::delete('/social-link/delete/{id}', 'Operator\SocialLinkController@destroy')->name('operator-sociallink-delete');
        Route::get('/social-link/status/{id1}/{id2}', 'Operator\SocialLinkController@status')->name('operator-sociallink-status');

        //------------ OPERATORSOCIAL LINK ENDS ------------
        Route::get('/social', 'Operator\SocialSettingController@index')->name('operator-social-index');
        Route::post('/social/update', 'Operator\SocialSettingController@socialupdate')->name('operator-social-update');
        Route::post('/social/update/all', 'Operator\SocialSettingController@socialupdateall')->name('operator-social-update-all');
        Route::get('/social/facebook', 'Operator\SocialSettingController@facebook')->name('operator-social-facebook');
        Route::get('/social/google', 'Operator\SocialSettingController@google')->name('operator-social-google');
        Route::get('/social/facebook/{status}', 'Operator\SocialSettingController@facebookup')->name('operator-social-facebookup');
        Route::get('/social/google/{status}', 'Operator\SocialSettingController@googleup')->name('operator-social-googleup');
    });
    //------------ OPERATORSOCIAL SETTINGS SECTION ENDS------------

    //------------ OPERATORLANGUAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:language_settings'], function () {

        //  Multiple Language Section

        //  Multiple Language Section Ends

        Route::get('/languages/datatables', 'Operator\LanguageController@datatables')->name('operator-lang-datatables'); //JSON REQUEST
        Route::get('/languages', 'Operator\LanguageController@index')->name('operator-lang-index');
        Route::get('/languages/create', 'Operator\LanguageController@create')->name('operator-lang-create');
        Route::get('/languages/import', 'Operator\LanguageController@import')->name('operator-lang-import');
        Route::get('/languages/edit/{id}', 'Operator\LanguageController@edit')->name('operator-lang-edit');
        Route::get('/languages/export/{id}', 'Operator\LanguageController@export')->name('operator-lang-export');
        Route::post('/languages/create', 'Operator\LanguageController@store')->name('operator-lang-store');
        Route::post('/languages/import/create', 'Operator\LanguageController@importStore')->name('operator-lang-import-store');
        Route::post('/languages/edit/{id}', 'Operator\LanguageController@update')->name('operator-lang-update');
        Route::get('/languages/status/{id1}/{id2}', 'Operator\LanguageController@status')->name('operator-lang-st');
        Route::delete('/languages/delete/{id}', 'Operator\LanguageController@destroy')->name('operator-lang-delete');


        //------------ OPERATORLANGUAGE SETTINGS SECTION ENDS ------------

    });

    //------------ADMIN FONT SECTION------------------
    Route::get('/fonts/datatables', 'Operator\FontController@datatables')->name('operator.fonts.datatables');
    Route::get('/fonts', 'Operator\FontController@index')->name('operator.fonts.index');
    Route::get('/fonts/create', 'Operator\FontController@create')->name('operator.fonts.create');
    Route::post('/fonts/create', 'Operator\FontController@store')->name('operator.fonts.store');
    Route::get('/fonts/edit/{id}', 'Operator\FontController@edit')->name('operator.fonts.edit');
    Route::post('/fonts/edit/{id}', 'Operator\FontController@update')->name('operator.fonts.update');
    Route::delete('/fonts/delete/{id}', 'Operator\FontController@destroy')->name('operator.fonts.delete');
    Route::get('/fonts/status/{id}', 'Operator\FontController@status')->name('operator.fonts.status');
    //------------ADMIN FONT SECTION------------------

    //------------ OPERATORSEOTOOL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:seo_tools'], function () {

        Route::get('/seotools/analytics', 'Operator\SeoToolController@analytics')->name('operator-seotool-analytics');
        Route::post('/seotools/analytics/update', 'Operator\SeoToolController@analyticsupdate')->name('operator-seotool-analytics-update');
        Route::get('/seotools/keywords', 'Operator\SeoToolController@keywords')->name('operator-seotool-keywords');
        Route::post('/seotools/keywords/update', 'Operator\SeoToolController@keywordsupdate')->name('operator-seotool-keywords-update');
        Route::get('/catalog-items/popular/{id}', 'Operator\SeoToolController@popular')->name('operator-catalog-item-popular');
    });

    //------------ OPERATORSEOTOOL SETTINGS SECTION ------------

    //------------ OPERATORSTAFF SECTION ------------

    Route::group(['middleware' => 'permissions:manage_staffs'], function () {

        Route::get('/staff/datatables', 'Operator\StaffController@datatables')->name('operator-staff-datatables');
        Route::get('/staff', 'Operator\StaffController@index')->name('operator-staff-index');
        Route::get('/staff/create', 'Operator\StaffController@create')->name('operator-staff-create');
        Route::post('/staff/create', 'Operator\StaffController@store')->name('operator-staff-store');
        Route::get('/staff/edit/{id}', 'Operator\StaffController@edit')->name('operator-staff-edit');
        Route::post('/staff/update/{id}', 'Operator\StaffController@update')->name('operator-staff-update');
        Route::get('/staff/show/{id}', 'Operator\StaffController@show')->name('operator-staff-show');
        Route::delete('/staff/delete/{id}', 'Operator\StaffController@destroy')->name('operator-staff-delete');
    });

    //------------ OPERATORSTAFF SECTION ENDS------------

    //------------ OPERATORMAILING LIST SECTION ------------

    Route::group(['middleware' => 'permissions:mailing_list'], function () {

        Route::get('/mailing-list/datatables', 'Operator\MailingListController@datatables')->name('operator-mailing-list-datatables'); //JSON REQUEST
        Route::get('/mailing-list', 'Operator\MailingListController@index')->name('operator-mailing-list-index');
        Route::get('/mailing-list/download', 'Operator\MailingListController@download')->name('operator-mailing-list-download');
    });

    //------------ OPERATORMAILING LIST ENDS ------------

    // ------------ GLOBAL ----------------------
    Route::post('/general-settings/update/all', 'Operator\MuaadhSettingController@generalupdate')->name('operator-gs-update');
    Route::post('/general-settings/update/te=heme', 'Operator\MuaadhSettingController@updateTheme')->name('operator-gs-update-theme');
    Route::post('/general-settings/update/payment', 'Operator\MuaadhSettingController@generalupdatepayment')->name('operator-gs-update-payment');
    Route::post('/general-settings/update/mail', 'Operator\MuaadhSettingController@generalMailUpdate')->name('operator-gs-update-mail');
    Route::get('/general-settings/status/{field}/{status}', 'Operator\MuaadhSettingController@status')->name('operator-gs-status');

    // Note: Status and Feature routes are now in the ADMIN CATALOG ITEM SECTION above

    // MERCHANT PHOTO SECTION ------------

    Route::get('/merchant-photo/show', 'Operator\MerchantPhotoController@show')->name('operator-merchant-photo-show');
    Route::post('/merchant-photo/store', 'Operator\MerchantPhotoController@store')->name('operator-merchant-photo-store');
    Route::get('/merchant-photo/delete', 'Operator\MerchantPhotoController@destroy')->name('operator-merchant-photo-delete');

    // MERCHANT PHOTO SECTION ENDS------------

    Route::post('/frontend-setting/update/all', 'Operator\FrontendSettingController@update')->name('operator-fs-update');
    Route::post('/frontend-setting/update/home', 'Operator\FrontendSettingController@homeupdate')->name('operator-fs-homeupdate');
    Route::post('/frontend-setting/menu-update', 'Operator\FrontendSettingController@menuupdate')->name('operator-fs-menuupdate');

    // ------------ GLOBAL ENDS ----------------------

    Route::group(['middleware' => 'permissions:super'], function () {

        Route::get('/cache/clear', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return redirect()->route('operator.dashboard')->with('cache', 'System Cache Has Been Removed.');
        })->name('operator-cache-clear');

        Route::get('/check/movescript', 'Operator\DashboardController@movescript')->name('operator-move-script');
        Route::get('/generate/backup', 'Operator\DashboardController@generate_bkup')->name('operator-generate-backup');
        Route::get('/clear/backup', 'Operator\DashboardController@clear_bkup')->name('operator-clear-backup');

        // ------------ LICENSE SECTION ----------------------
        Route::get('/license/datatables', 'Operator\LicenseController@datatables')->name('operator-license-datatables');
        Route::get('/license', 'Operator\LicenseController@index')->name('operator-license-index');
        Route::get('/license/create', 'Operator\LicenseController@create')->name('operator-license-create');
        Route::post('/license/create', 'Operator\LicenseController@store')->name('operator-license-store');
        Route::get('/license/edit/{id}', 'Operator\LicenseController@edit')->name('operator-license-edit');
        Route::post('/license/edit/{id}', 'Operator\LicenseController@update')->name('operator-license-update');
        Route::delete('/license/delete/{id}', 'Operator\LicenseController@destroy')->name('operator-license-delete');
        Route::get('/license/activate/{id}', 'Operator\LicenseController@activateLicense')->name('operator-license-activate-license');
        Route::get('/license/deactivate/{id}', 'Operator\LicenseController@deactivate')->name('operator-license-deactivate');
        Route::get('/license/generate-key', 'Operator\LicenseController@generateKey')->name('operator-license-generate-key');
        Route::get('/activation', 'Operator\LicenseController@activation')->name('operator-activation-form');
        Route::post('/activation', 'Operator\LicenseController@activateWithKey')->name('operator-activate-purchase');
        // ------------ LICENSE SECTION ENDS ----------------------

        // ------------ ADMIN ROLE SECTION ----------------------

        Route::get('/admin-role/datatables', 'Operator\RoleController@datatables')->name('operator-role-datatables');
        Route::get('/admin-role', 'Operator\RoleController@index')->name('operator-role-index');
        Route::get('/admin-role/create', 'Operator\RoleController@create')->name('operator-role-create');
        Route::post('/admin-role/create', 'Operator\RoleController@store')->name('operator-role-store');
        Route::get('/admin-role/edit/{id}', 'Operator\RoleController@edit')->name('operator-role-edit');
        Route::post('/admin-role/edit/{id}', 'Operator\RoleController@update')->name('operator-role-update');
        Route::delete('/admin-role/delete/{id}', 'Operator\RoleController@destroy')->name('operator-role-delete');

        // ------------ ADMIN ROLE SECTION ENDS ----------------------

        // ------------ MODULE SECTION ----------------------

        Route::get('/module/datatables', 'Operator\ModuleController@datatables')->name('operator-module-datatables');
        Route::get('/module', 'Operator\ModuleController@index')->name('operator-module-index');
        Route::get('/module/create', 'Operator\ModuleController@create')->name('operator-module-create');
        Route::post('/module/install', 'Operator\ModuleController@install')->name('operator-module-install');
        Route::get('/module/uninstall/{id}', 'Operator\ModuleController@uninstall')->name('operator-module-uninstall');

        // ------------ MODULE SECTION ENDS ----------------------

    });

});

// ************************************ OPERATOR SECTION ENDS**********************************************

Route::group(['middleware' => 'maintenance'], function () {

    // ************************************ MERCHANT SECTION **********************************************

    Route::prefix('merchant')->group(function () {

        Route::group(['middleware' => 'merchant'], function () {

            // MERCHANT DASHBOARD

            Route::get('/dashboard', 'Merchant\MerchantController@index')->name('merchant.dashboard');

            //------------ PURCHASE SECTION ------------

            Route::get('/purchases/datatables', 'Merchant\PurchaseController@datatables')->name('merchant-purchase-datatables');
            Route::get('/purchases', 'Merchant\PurchaseController@index')->name('merchant-purchase-index');
            Route::get('/purchase/{id}/show', 'Merchant\PurchaseController@show')->name('merchant-purchase-show');
            Route::get('/purchase/{id}/invoice', 'Merchant\PurchaseController@invoice')->name('merchant-purchase-invoice');
            Route::get('/purchase/{id}/print', 'Merchant\PurchaseController@printpage')->name('merchant-purchase-print');
            Route::get('/purchase/{id1}/status/{status}', 'Merchant\PurchaseController@status')->name('merchant-purchase-status');
            Route::post('/purchase/email/', 'Merchant\PurchaseController@emailsub')->name('merchant-purchase-emailsub');
            Route::post('/purchase/{slug}/license', 'Merchant\PurchaseController@license')->name('merchant-purchase-license');

            //------------ PURCHASE SECTION ENDS------------

            Route::get('delivery/datatables', 'Merchant\DeliveryController@datatables')->name('merchant-delivery-purchase-datatables');
            Route::get('delivery', 'Merchant\DeliveryController@index')->name('merchant.delivery.index');
            Route::get('delivery/boy/find', 'Merchant\DeliveryController@findCourier')->name('merchant.find.courier');
            Route::post('courier/search/submit', 'Merchant\DeliveryController@findCourierSubmit')->name('merchant-courier-search-submit');

            // Tryoto Shipping Routes
            Route::get('delivery/shipping-options', 'Merchant\DeliveryController@getShippingOptions')->name('merchant.shipping.options');
            Route::post('delivery/send-to-tryoto', 'Merchant\DeliveryController@sendToTryoto')->name('merchant.send.tryoto');
            Route::get('delivery/track-shipment', 'Merchant\DeliveryController@trackShipment')->name('merchant.track.shipment');
            Route::get('delivery/shipment-history/{purchaseId}', 'Merchant\DeliveryController@shipmentHistory')->name('merchant.shipment.history');
            Route::post('delivery/cancel-shipment', 'Merchant\DeliveryController@cancelShipment')->name('merchant.cancel.shipment');
            Route::post('delivery/ready-for-pickup', 'Merchant\DeliveryController@markReadyForPickup')->name('merchant.ready.pickup');
            Route::get('delivery/stats', 'Merchant\DeliveryController@shippingStats')->name('merchant.shipping.stats');
            Route::get('delivery/purchase-status/{purchaseId}', 'Merchant\DeliveryController@getPurchaseShipmentStatus')->name('merchant.purchase.shipment.status');

            //------------ SUBCATEGORY SECTION ------------

            Route::get('/load/subcategories/{id}/', 'Merchant\MerchantController@subcatload')->name('merchant-subcat-load'); //JSON REQUEST

            //------------ SUBCATEGORY SECTION ENDS------------

            //------------ CHILDCATEGORY SECTION ------------

            Route::get('/load/childcategories/{id}/', 'Merchant\MerchantController@childcatload')->name('merchant-childcat-load'); //JSON REQUEST

            //------------ CHILDCATEGORY SECTION ENDS------------

            //------------ MERCHANT CATALOG ITEM SECTION ------------

            Route::get('/catalog-items/datatables', 'Merchant\CatalogItemController@datatables')->name('merchant-catalog-item-datatables');
            Route::get('/catalog-items', 'Merchant\CatalogItemController@index')->name('merchant-catalog-item-index');
            Route::post('/catalog-items/upload/update/{id}', 'Merchant\CatalogItemController@uploadUpdate')->name('merchant-catalog-item-upload-update');

            // CREATE SECTION
            Route::get('/catalog-items/add', 'Merchant\CatalogItemController@add')->name('merchant-catalog-item-add');
            Route::get('/catalog-items/search-part_number', 'Merchant\CatalogItemController@searchSku')->name('merchant-catalog-item-search-part_number');
            Route::post('/catalog-items/store-offer', 'Merchant\CatalogItemController@storeOffer')->name('merchant-catalog-item-store-offer');
            Route::put('/catalog-items/update-offer/{merchantItemId}', 'Merchant\CatalogItemController@updateOffer')->name('merchant-catalog-item-update-offer');
            Route::get('/catalog-items/types', 'Merchant\CatalogItemController@types')->name('merchant-catalog-item-types');
            Route::get('/catalog-items/{slug}/create', 'Merchant\CatalogItemController@create')->name('merchant-catalog-item-create');
            Route::post('/catalog-items/store', 'Merchant\CatalogItemController@store')->name('merchant-catalog-item-store');
            Route::get('/getspecs', 'Merchant\CatalogItemController@getSpecs')->name('merchant-catalog-item-getspecs');
            Route::get('/catalog-items/import', 'Merchant\CatalogItemController@import')->name('merchant-catalog-item-import');
            Route::post('/catalog-items/import-submit', 'Merchant\CatalogItemController@importSubmit')->name('merchant-catalog-item-importsubmit');
            Route::get('/catalog-items/catalog/datatables', 'Merchant\CatalogItemController@catalogdatatables')->name('merchant-catalog-item-catalog-datatables');
            Route::get('/catalog-items/catalogs', 'Merchant\CatalogItemController@catalogs')->name('merchant-catalog-item-catalogs');
            Route::get('/catalog-items/create-offer/{catalog_item_id}', 'Merchant\CatalogItemController@createOffer')->name('merchant-catalog-item-create-offer');

            // EDIT SECTION
            Route::get('/catalog-items/edit/{merchantItemId}', 'Merchant\CatalogItemController@edit')->name('merchant-catalog-item-edit');
            Route::post('/catalog-items/edit/{merchantItemId}', 'Merchant\CatalogItemController@update')->name('merchant-catalog-item-update');
            Route::get('/catalog-items/catalog/{id}', 'Merchant\CatalogItemController@catalogedit')->name('merchant-catalog-item-catalog-edit');
            Route::post('/catalog-items/catalog/{id}', 'Merchant\CatalogItemController@catalogupdate')->name('merchant-catalog-item-catalog-update');

            // IMPORT SECTION
            Route::get('/catalog-items/import/create', 'Merchant\ImportController@createImport')->name('merchant-import-create');
            Route::get('/catalog-items/import/edit/{id}', 'Merchant\ImportController@edit')->name('merchant-import-edit');
            Route::get('/catalog-items/import/csv', 'Merchant\ImportController@importCSV')->name('merchant-import-csv');
            Route::get('/catalog-items/import/datatables', 'Merchant\ImportController@datatables')->name('merchant-import-datatables');
            Route::get('/catalog-items/import/index', 'Merchant\ImportController@index')->name('merchant-import-index');
            Route::post('/catalog-items/import/store', 'Merchant\ImportController@store')->name('merchant-import-store');
            Route::post('/catalog-items/import/update/{id}', 'Merchant\ImportController@update')->name('merchant-import-update');
            Route::post('/catalog-items/import/csv/store', 'Merchant\ImportController@importStore')->name('merchant-import-csv-store');

            // STATUS SECTION
            Route::get('/catalog-items/status/{id1}/{id2}', 'Merchant\CatalogItemController@status')->name('merchant-catalog-item-status');

            // DELETE SECTION
            Route::delete('/catalog-items/delete/{id}', 'Merchant\CatalogItemController@destroy')->name('merchant-catalog-item-delete');

            //------------ MERCHANT CATALOG ITEM SECTION ENDS------------

            //------------ STOCK MANAGEMENT SECTION ------------
            Route::get('/stock/management', 'Merchant\StockManagementController@index')->name('merchant-stock-management');
            Route::get('/stock/datatables', 'Merchant\StockManagementController@datatables')->name('merchant-stock-datatables');
            Route::get('/stock/export', 'Merchant\StockManagementController@export')->name('merchant-stock-export');
            Route::get('/stock/upload-form', 'Merchant\StockManagementController@uploadForm')->name('merchant-stock-upload-form');
            Route::post('/stock/upload', 'Merchant\StockManagementController@upload')->name('merchant-stock-upload');
            Route::get('/stock/download/{id}', 'Merchant\StockManagementController@download')->name('merchant-stock-download');
            Route::post('/stock/auto-update', 'Merchant\StockManagementController@triggerAutoUpdate')->name('merchant-stock-auto-update');
            Route::post('/stock/full-refresh', 'Merchant\StockManagementController@triggerFullRefresh')->name('merchant-stock-full-refresh');
            Route::post('/stock/process-full-refresh', 'Merchant\StockManagementController@processFullRefresh')->name('merchant-stock-process-full-refresh');
            Route::get('/stock/progress/{id}', 'Merchant\StockManagementController@getUpdateProgress')->name('merchant-stock-progress');
            Route::get('/stock/template', 'Merchant\StockManagementController@downloadTemplate')->name('merchant-stock-template');
            //------------ STOCK MANAGEMENT SECTION ENDS ------------

            //------------ MERCHANT PHOTO SECTION ------------

            Route::get('/merchant-photo/show', 'Merchant\MerchantPhotoController@show')->name('merchant-merchant-photo-show');
            Route::post('/merchant-photo/store', 'Merchant\MerchantPhotoController@store')->name('merchant-merchant-photo-store');
            Route::get('/merchant-photo/delete', 'Merchant\MerchantPhotoController@destroy')->name('merchant-merchant-photo-delete');

            //------------ MERCHANT PHOTO SECTION ENDS------------

            //------------ MERCHANT SHIPPING ------------

            Route::get('/shipping/datatables', 'Merchant\ShippingController@datatables')->name('merchant-shipping-datatables');
            Route::get('/shipping', 'Merchant\ShippingController@index')->name('merchant-shipping-index');
            Route::get('/shipping/create', 'Merchant\ShippingController@create')->name('merchant-shipping-create');
            Route::post('/shipping/create', 'Merchant\ShippingController@store')->name('merchant-shipping-store');
            Route::get('/shipping/edit/{id}', 'Merchant\ShippingController@edit')->name('merchant-shipping-edit');
            Route::post('/shipping/edit/{id}', 'Merchant\ShippingController@update')->name('merchant-shipping-update');
            Route::delete('/shipping/delete/{id}', 'Merchant\ShippingController@destroy')->name('merchant-shipping-delete');

            //------------ MERCHANT SHIPPING ENDS ------------

            //------------ MERCHANT WAREHOUSE SETTINGS ------------

            Route::get('/warehouse', 'Merchant\WarehouseController@index')->name('merchant-warehouse-index');
            Route::post('/warehouse/update', 'Merchant\WarehouseController@update')->name('merchant-warehouse-update');
            Route::get('/warehouse/get-cities', 'Merchant\WarehouseController@getCities')->name('merchant-warehouse-get-cities');

            //------------ MERCHANT WAREHOUSE ENDS ------------

            //------------ MERCHANT PACKAGE ------------

            Route::get('/package/datatables', 'Merchant\PackageController@datatables')->name('merchant-package-datatables');
            Route::get('/package', 'Merchant\PackageController@index')->name('merchant-package-index');
            Route::get('/package/create', 'Merchant\PackageController@create')->name('merchant-package-create');
            Route::post('/package/create', 'Merchant\PackageController@store')->name('merchant-package-store');
            Route::get('/package/edit/{id}', 'Merchant\PackageController@edit')->name('merchant-package-edit');
            Route::post('/package/edit/{id}', 'Merchant\PackageController@update')->name('merchant-package-update');
            Route::delete('/package/delete/{id}', 'Merchant\PackageController@destroy')->name('merchant-package-delete');

            //------------ MERCHANT PACKAGE ENDS------------

            //------------ MERCHANT NOTIFICATION SECTION ------------

            Route::get('/purchase/notf/show/{id}', 'Merchant\NotificationController@purchase_notf_show')->name('merchant-purchase-notf-show');
            Route::get('/purchase/notf/count/{id}', 'Merchant\NotificationController@purchase_notf_count')->name('merchant-purchase-notf-count');
            Route::get('/purchase/notf/clear/{id}', 'Merchant\NotificationController@purchase_notf_clear')->name('merchant-purchase-notf-clear');

            //------------ MERCHANT NOTIFICATION SECTION ENDS ------------

            // Merchant Profile
            Route::get('/profile', 'Merchant\MerchantController@profile')->name('merchant-profile');
            Route::post('/profile', 'Merchant\MerchantController@profileupdate')->name('merchant-profile-update');
            // Merchant Profile Ends

            // Merchant Shipping Cost
            Route::get('/banner', 'Merchant\MerchantController@banner')->name('merchant-banner');

            // Merchant Social
            Route::get('/social', 'Merchant\MerchantController@social')->name('merchant-social-index');
            Route::post('/social/update', 'Merchant\MerchantController@socialupdate')->name('merchant-social-update');

            Route::get('/withdraw/datatables', 'Merchant\WithdrawController@datatables')->name('merchant-wt-datatables');
            Route::get('/withdraw', 'Merchant\WithdrawController@index')->name('merchant-wt-index');
            Route::get('/withdraw/create', 'Merchant\WithdrawController@create')->name('merchant-wt-create');
            Route::post('/withdraw/create', 'Merchant\WithdrawController@store')->name('merchant-wt-store');

            //------------ MERCHANT SERVICE ------------

            Route::get('/service/datatables', 'Merchant\ServiceController@datatables')->name('merchant-service-datatables');
            Route::get('/service', 'Merchant\ServiceController@index')->name('merchant-service-index');
            Route::get('/service/create', 'Merchant\ServiceController@create')->name('merchant-service-create');
            Route::post('/service/create', 'Merchant\ServiceController@store')->name('merchant-service-store');
            Route::get('/service/edit/{id}', 'Merchant\ServiceController@edit')->name('merchant-service-edit');
            Route::post('/service/edit/{id}', 'Merchant\ServiceController@update')->name('merchant-service-update');
            Route::delete('/service/delete/{id}', 'Merchant\ServiceController@destroy')->name('merchant-service-delete');

            //------------ MERCHANT SERVICE ENDS ------------

            //------------ MERCHANT PICKUP POINT ------------
            Route::get('/pickup-point/datatables', 'Merchant\PickupPointController@datatables')->name('merchant-pickup-point-datatables');
            Route::get('/pickup-point', 'Merchant\PickupPointController@index')->name('merchant-pickup-point-index');
            Route::get('/pickup-point/create', 'Merchant\PickupPointController@create')->name('merchant-pickup-point-create');
            Route::post('/pickup-point/create', 'Merchant\PickupPointController@store')->name('merchant-pickup-point-store');
            Route::get('/pickup-point/edit/{id}', 'Merchant\PickupPointController@edit')->name('merchant-pickup-point-edit');
            Route::post('/pickup-point/edit/{id}', 'Merchant\PickupPointController@update')->name('merchant-pickup-point-update');
            Route::delete('/pickup-point/delete/{id}', 'Merchant\PickupPointController@destroy')->name('merchant-pickup-point-delete');
            Route::get('/pickup-point/status/{id}/{status}', 'Merchant\PickupPointController@status')->name('merchant-pickup-point-status');

            //------------ MERCHANT PICKUP POINT END ------------

            //------------ MERCHANT SOCIAL LINK ------------

            Route::get('/social-link/datatables', 'Merchant\SocialLinkController@datatables')->name('merchant-sociallink-datatables'); //JSON REQUEST
            Route::get('/social-link', 'Merchant\SocialLinkController@index')->name('merchant-sociallink-index');
            Route::get('/social-link/create', 'Merchant\SocialLinkController@create')->name('merchant-sociallink-create');
            Route::post('/social-link/create', 'Merchant\SocialLinkController@store')->name('merchant-sociallink-store');
            Route::get('/social-link/edit/{id}', 'Merchant\SocialLinkController@edit')->name('merchant-sociallink-edit');
            Route::post('/social-link/edit/{id}', 'Merchant\SocialLinkController@update')->name('merchant-sociallink-update');
            Route::delete('/social-link/delete/{id}', 'Merchant\SocialLinkController@destroy')->name('merchant-sociallink-delete');
            Route::get('/social-link/status/{id1}/{id2}', 'Merchant\SocialLinkController@status')->name('merchant-sociallink-status');

            //------------ MERCHANT SOCIAL LINK ENDS ------------

            //------------ MERCHANT SHIPMENTS SECTION ------------

            Route::get('/shipments', 'Merchant\ShipmentController@index')->name('merchant.shipments.index');
            Route::get('/shipments/show/{tracking}', 'Merchant\ShipmentController@show')->name('merchant.shipments.show');
            Route::get('/shipments/refresh/{tracking}', 'Merchant\ShipmentController@refresh')->name('merchant.shipments.refresh');
            Route::post('/shipments/cancel/{tracking}', 'Merchant\ShipmentController@cancel')->name('merchant.shipments.cancel');
            Route::get('/shipments/export', 'Merchant\ShipmentController@export')->name('merchant.shipments.export');
            Route::post('/shipments/bulk-refresh', 'Merchant\ShipmentController@bulkRefresh')->name('merchant.shipments.bulk-refresh');

            //------------ MERCHANT SHIPMENTS SECTION ENDS------------

            //------------ MERCHANT DISCOUNT CODE SECTION ------------

            Route::get('/discount-code/datatables', 'Merchant\DiscountCodeController@datatables')->name('merchant-discount-code-datatables');
            Route::get('/discount-code', 'Merchant\DiscountCodeController@index')->name('merchant-discount-code-index');
            Route::get('/discount-code/create', 'Merchant\DiscountCodeController@create')->name('merchant-discount-code-create');
            Route::post('/discount-code/create', 'Merchant\DiscountCodeController@store')->name('merchant-discount-code-store');
            Route::get('/discount-code/edit/{id}', 'Merchant\DiscountCodeController@edit')->name('merchant-discount-code-edit');
            Route::post('/discount-code/edit/{id}', 'Merchant\DiscountCodeController@update')->name('merchant-discount-code-update');
            Route::delete('/discount-code/delete/{id}', 'Merchant\DiscountCodeController@destroy')->name('merchant-discount-code-delete');
            Route::get('/discount-code/status/{id1}/{id2}', 'Merchant\DiscountCodeController@status')->name('merchant-discount-code-status');
            Route::get('/discount-code/get-categories', 'Merchant\DiscountCodeController@getCategories')->name('merchant-discount-code-get-categories');

            //------------ MERCHANT DISCOUNT CODE SECTION ENDS------------

            //------------ MERCHANT CREDENTIALS SECTION ------------
            Route::get('/credentials', 'Merchant\CredentialController@index')->name('merchant-credentials-index');
            Route::get('/credentials/create', 'Merchant\CredentialController@create')->name('merchant-credentials-create');
            Route::post('/credentials/store', 'Merchant\CredentialController@store')->name('merchant-credentials-store');
            Route::get('/credentials/edit/{id}', 'Merchant\CredentialController@edit')->name('merchant-credentials-edit');
            Route::put('/credentials/update/{id}', 'Merchant\CredentialController@update')->name('merchant-credentials-update');
            Route::post('/credentials/toggle/{id}', 'Merchant\CredentialController@toggle')->name('merchant-credentials-toggle');
            Route::delete('/credentials/delete/{id}', 'Merchant\CredentialController@destroy')->name('merchant-credentials-destroy');
            //------------ MERCHANT CREDENTIALS SECTION ENDS------------

            // -------------------------- Merchant Income ------------------------------------//
            Route::get('earning/datatables', "Merchant\IncomeController@datatables")->name('merchant.income.datatables');
            Route::get('total/earning', "Merchant\IncomeController@index")->name('merchant.income');

            Route::get('/verify', 'Merchant\MerchantController@verify')->name('merchant-verify');
            Route::get('/warning/verify/{id}', 'Merchant\MerchantController@warningVerify')->name('merchant-warning');
            Route::post('/verify', 'Merchant\MerchantController@verifysubmit')->name('merchant-verify-submit');
        });
    });

    // ************************************ MERCHANT SECTION ENDS**********************************************

    // ************************************ USER SECTION **********************************************

    Route::get('user/success/{status}', function ($status) {
        return view('user.success', compact('status'));
    })->name('user.success');

    Route::prefix('user')->group(function () {
        
        

        // USER AUTH SECION
        Route::get('/login', 'User\LoginController@showLoginForm')->name('user.login');
        Route::get('/login/with/otp', 'User\LoginController@showOtpLoginForm')->name('user.otp.login');
        Route::post('/login/with/otp/submit', 'User\LoginController@showOtpLoginFormSubmit')->name('user.opt.login.submit');
        Route::get('/login/with/otp/view', 'User\LoginController@showOtpLoginFormView')->name('user.opt.login.view');
        Route::post('/login/with/otp/view/submit', 'User\LoginController@showOtpLoginFormViewSubmit')->name('user.opt.login.view.submit');
        Route::get('/merchant-login', 'User\LoginController@showMerchantLoginForm')->name('merchant.login');

        Route::get('/register', 'User\RegisterController@showRegisterForm')->name('user.register');
        Route::get('/merchant-register', 'User\RegisterController@showMerchantRegisterForm')->name('merchant.register');
        // User Login
        Route::post('/login', 'Auth\User\LoginController@login')->name('user.login.submit');
        // User Login End

        // User Register
        Route::post('/register', 'Auth\User\RegisterController@register')->name('user-register-submit');
        Route::get('/register/verify/{token}', 'Auth\User\RegisterController@token')->name('user-register-token');
        // User Register End

        //------------ USER FORGOT SECTION ------------
        Route::get('/forgot', 'Auth\User\ForgotController@index')->name('user.forgot');
        Route::post('/forgot', 'Auth\User\ForgotController@forgot')->name('user.forgot.submit');
        Route::get('/change-password/{token}', 'Auth\User\ForgotController@showChangePassForm')->name('user.change.token');
        Route::post('/change-password', 'Auth\User\ForgotController@changepass')->name('user.change.password');

        //------------ USER FORGOT SECTION ENDS ------------

        //  --------------------- Reward Point Route ------------------------------//
        Route::get('reward/points', 'User\RewardController@rewards')->name('user-reward-index');
        Route::get('reward/convert', 'User\RewardController@convert')->name('user-reward-convernt');
        Route::post('reward/convert/submit', 'User\RewardController@convertSubmit')->name('user-reward-convert-submit');

        Route::get('/logout', 'User\LoginController@logout')->name('user-logout');
        Route::get('/dashboard', 'User\UserController@index')->name('user-dashboard');

      
        // User Reset
        Route::get('/reset', 'User\UserController@resetform')->name('user-reset');
        Route::post('/reset', 'User\UserController@reset')->name('user-reset-submit');
        // User Reset End

        // User Profile
        Route::get('/profile', 'User\UserController@profile')->name('user-profile');
        Route::post('/profile', 'User\UserController@profileupdate')->name('user-profile-update');
        // User Profile Ends

        // Display important Codes For Payment Gatweways
        Route::get('/payment/{slug1}/{slug2}', 'User\UserController@loadpayment')->name('user.load.payment');
        // Get cities by country (states removed)
        Route::get('/country/wise/city/{country_id}', 'Front\CheckoutController@getCity')->name('country.wise.city');
        Route::get('/user/country/wise/city', 'Front\CheckoutController@getCityUser')->name('country.wise.city.user');

        // User Favorites
        Route::get('/favorites', 'User\FavoriteController@favorites')->name('user-favorites');

        Route::get('/favorite/add/merchant/{merchantItemId}', 'User\FavoriteController@add')->name('user-favorite-add-merchant');

        Route::get('/favorite/add/{id}', 'User\FavoriteController@addLegacy')->name('user-favorite-add');
        Route::get('/favorite/remove/{id}', 'User\FavoriteController@remove')->name('user-favorite-remove');
        // User Favorites Ends

        // User Review
        Route::post('/review/submit', 'User\UserController@reviewsubmit')->name('front.review.submit');
        // User Review Ends

        // User Purchases

        Route::get('/purchases', 'User\PurchaseController@purchases')->name('user-purchases');
        Route::get('/purchase/tracking', 'User\PurchaseController@purchasetrack')->name('user-purchase-track');
        Route::get('/purchase/trackings/{id}', 'User\PurchaseController@trackload')->name('user-purchase-track-search');
        Route::get('/purchase/{id}', 'User\PurchaseController@purchase')->name('user-purchase');
        Route::get('/download/purchase/{slug}/{id}', 'User\PurchaseController@purchasedownload')->name('user-purchase-download');
        Route::get('print/purchase/print/{id}', 'User\PurchaseController@purchaseprint')->name('user-purchase-print');
        Route::get('/json/trans', 'User\PurchaseController@trans');

        // User Purchases Ends

        // USER TOP-UP & WALLET LOGS

        Route::get('/top-up/wallet-logs', 'User\TopUpController@walletLogs')->name('user-wallet-logs-index');
        Route::get('/top-up/wallet-logs/{id}/show', 'User\TopUpController@transhow')->name('user-wallet-log-show');
        Route::get('/top-up/index', 'User\TopUpController@index')->name('user-top-up-index');
        Route::get('/top-up/create', 'User\TopUpController@create')->name('user-top-up-create');

        // Top Up Payment Redirect
        Route::get('/top-up/payment/cancle', 'User\TopUpController@paycancle')->name('top-up.payment.cancle');
        Route::get('/top-up/payment/return', 'User\TopUpController@payreturn')->name('top-up.payment.return');

        // Paypal
        Route::post('/topup/paypal-submit', 'Payment\TopUp\PaypalController@store')->name('topup.paypal.submit');
        Route::get('/topup/paypal-notify', 'Payment\TopUp\PaypalController@notify')->name('topup.paypal.notify');

        // Stripe
        Route::post('/topup/stripe-submit', 'Payment\TopUp\StripeController@store')->name('topup.stripe.submit');
        Route::get('/topup/stripe/notify', 'Payment\TopUp\StripeController@notify')->name('topup.stripe.notify');

        // Instamojo
        Route::post('/topup/instamojo-submit', 'Payment\TopUp\InstamojoController@store')->name('topup.instamojo.submit');
        Route::get('/topup/instamojo-notify', 'Payment\TopUp\InstamojoController@notify')->name('topup.instamojo.notify');

        // Paystack
        Route::post('/topup/paystack-submit', 'Payment\TopUp\PaystackController@store')->name('topup.paystack.submit');

        // PayTM
        Route::post('/topup/paytm-submit', 'Payment\TopUp\PaytmController@store')->name('topup.paytm.submit');;
        Route::post('/topup/paytm-notify', 'Payment\TopUp\PaytmController@notify')->name('topup.paytm.notify');

        // Molly
        Route::post('/topup/molly-submit', 'Payment\TopUp\MollieController@store')->name('topup.molly.submit');
        Route::get('/topup/molly-notify', 'Payment\TopUp\MollieController@notify')->name('topup.molly.notify');

        // RazorPay
        Route::post('/topup/razorpay-submit', 'Payment\TopUp\RazorpayController@store')->name('topup.razorpay.submit');
        Route::post('/topup/razorpay-notify', 'Payment\TopUp\RazorpayController@notify')->name('topup.razorpay.notify');

        // Authorize.Net
        Route::post('/topup/authorize-submit', 'Payment\TopUp\AuthorizeController@store')->name('topup.authorize.submit');

        // Mercadopago
        Route::post('/topup/mercadopago-submit', 'Payment\TopUp\MercadopagoController@store')->name('topup.mercadopago.submit');

        // Flutter Wave
        Route::post('/topup/flutter-submit', 'Payment\TopUp\FlutterwaveController@store')->name('topup.flutter.submit');

        // SSLCommerz
        Route::post('/topup/ssl-submit', 'Payment\TopUp\SslController@store')->name('topup.ssl.submit');
        Route::post('/topup/ssl-notify', 'Payment\TopUp\SslController@notify')->name('topup.ssl.notify');

        // Voguepay
        Route::post('/topup/voguepay-submit', 'Payment\TopUp\VoguepayController@store')->name('topup.voguepay.submit');

        // Manual
        Route::post('/topup/manual-submit', 'Payment\TopUp\ManualPaymentController@store')->name('topup.manual.submit');

        // USER TOP UP ENDS

        // User Merchant Chat

        Route::post('/user/contact', 'User\ChatController@usercontact')->name('user-contact');
        Route::get('/chats', 'User\ChatController@messages')->name('user-chats');
        Route::get('/chat/{id}', 'User\ChatController@message')->name('user-chat');
        Route::post('/chat/post', 'User\ChatController@postmessage')->name('user-chat-post');
        Route::get('/chat/{id}/delete', 'User\ChatController@messagedelete')->name('user-chat-delete');
        Route::get('/chat/load/{id}', 'User\ChatController@msgload')->name('user-chat-load');

        // User Merchant Chat Ends

        // User Support Tickets

        // Tickets
        Route::get('admin/tickets', 'User\ChatController@adminmessages')->name('user-ticket-index');
        // Disputes
        Route::get('admin/disputes', 'User\ChatController@adminDiscordmessages')->name('user-dispute-index');

        Route::get('admin/ticket/{id}', 'User\ChatController@adminmessage')->name('user-ticket-show');
        Route::post('admin/ticket/post', 'User\ChatController@adminpostmessage')->name('user-ticket-store');
        Route::get('admin/ticket/{id}/delete', 'User\ChatController@adminmessagedelete')->name('user-ticket-delete');
        Route::post('admin/user/send/ticket', 'User\ChatController@adminusercontact')->name('user-send-ticket');
        Route::get('admin/ticket/load/{id}', 'User\ChatController@messageload')->name('user-ticket-load');
        // User Support Tickets Ends

        Route::get('/affilate/program', 'User\UserController@affilate_code')->name('user-affilate-program');
        Route::get('/affilate/history', 'User\UserController@affilate_history')->name('user-affilate-history');

        Route::get('/affilate/withdraw', 'User\WithdrawController@index')->name('user-wwt-index');
        Route::get('/affilate/withdraw/create', 'User\WithdrawController@create')->name('user-wwt-create');
        Route::post('/affilate/withdraw/create', 'User\WithdrawController@store')->name('user-wwt-store');

        // User Favorite Seller

        Route::get('/favorite/seller', 'User\UserController@favorites')->name('user-favorites');
        Route::get('/favorite/{id1}/{id2}', 'User\UserController@favorite')->name('user-favorite');
        Route::get('/favorite/seller/{id}/delete', 'User\UserController@favdelete')->name('user-favorite-delete');

        // Mobile TopUp Route section

        Route::get('/api/checkout/instamojo/notify', 'Api\User\Payment\InstamojoController@notify')->name('api.user.topup.instamojo.notify');

        Route::post('/api/paystack/submit', 'Api\User\Payment\PaystackController@store')->name('api.user.topup.paystack.submit');
        // Route::post('/api/voguepay/submit', 'Api\User\Payment\VoguepayController@store')->name('api.user.topup.voguepay.submit'); // Controller file missing

        Route::post('/api/instamojo/submit', 'Api\User\Payment\InstamojoController@store')->name('api.user.topup.instamojo.submit');
        Route::post('/api/paypal-submit', 'Api\User\Payment\PaymentController@store')->name('api.user.topup.paypal.submit');
        Route::get('/api/paypal/notify', 'Api\User\Payment\PaymentController@notify')->name('api.user.topup.payment.notify');
        Route::post('/api/authorize-submit', 'Api\User\Payment\AuthorizeController@store')->name('api.user.topup.authorize.submit');

        Route::post('/api/payment/stripe-submit', 'Api\User\Payment\StripeController@store')->name('api.user.topup.stripe.submit');
        Route::get('/api/payment/stripe/notify', 'Api\User\Payment\StripeController@notify')->name('api.user.topup.stripe.notify');

        // ssl Routes
        Route::post('/api/ssl/submit', 'Api\User\Payment\SslController@store')->name('api.user.topup.ssl.submit');
        Route::post('/api/ssl/notify', 'Api\User\Payment\SslController@notify')->name('api.user.topup.ssl.notify');
        Route::post('/api/ssl/cancle', 'Api\User\Payment\SslController@cancle')->name('api.user.topup.ssl.cancle');

        // Molly Routes
        Route::post('/api/molly/submit', 'Api\User\Payment\MollyController@store')->name('api.user.topup.molly.submit');
        Route::get('/api/molly/notify', 'Api\User\Payment\MollyController@notify')->name('api.user.topup.molly.notify');

        //PayTM Routes
        Route::post('/api/paytm-submit', 'Api\User\Payment\PaytmController@store')->name('api.user.topup.paytm.submit');;
        Route::post('/api/paytm-callback', 'Api\User\Payment\PaytmController@paytmCallback')->name('api.user.topup.paytm.notify');

        //RazorPay Routes
        Route::post('/api/razorpay-submit', 'Api\User\Payment\RazorpayController@store')->name('api.user.topup.razorpay.submit');;
        Route::post('/api/razorpay-callback', 'Api\User\Payment\RazorpayController@razorCallback')->name('api.user.topup.razorpay.notify');

        // Mercadopago Routes
        Route::get('/api/checkout/mercadopago/return', 'Api\User\Payment\MercadopagoController@payreturn')->name('api.user.topup.mercadopago.return');
        Route::post('/api/checkout/mercadopago/notify', 'Api\User\Payment\MercadopagoController@notify')->name('api.user.topup.mercadopago.notify');
        Route::post('/api/checkout/mercadopago/submit', 'Api\User\Payment\MercadopagoController@store')->name('api.user.topup.mercadopago.submit');
        // Flutterwave Routes
        Route::post('/api/flutter/submit', 'Api\User\Payment\FlutterWaveController@store')->name('api.user.topup.flutter.submit');
        Route::post('/api/flutter/notify', 'Api\User\Payment\FlutterWaveController@notify')->name('api.user.topup.flutter.notify');

        // Mobile TopUp Route section

    });

    // ************************************ USER SECTION ENDS**********************************************

    // ************************************ COURIER SECTION **********************************************
    Route::prefix('courier')->group(function () {

        // COURIER AUTH SECTION
        Route::get('/login', 'Courier\LoginController@showLoginForm')->name('courier.login');
        Route::post('/login', 'Auth\Courier\LoginController@login')->name('courier.login.submit');
        Route::get('/success/{status}', 'Courier\LoginController@status')->name('courier.success');

        Route::get('/register', 'Courier\RegisterController@showRegisterForm')->name('courier.register');

        // Courier Register
        Route::post('/register', 'Auth\Courier\RegisterController@register')->name('courier-register-submit');
        Route::get('/register/verify/{token}', 'Auth\Courier\RegisterController@token')->name('courier-register-token');
        // Courier Register End

        //------------ COURIER FORGOT SECTION ------------
        Route::get('/forgot', 'Auth\Courier\ForgotController@index')->name('courier.forgot');
        Route::post('/forgot', 'Auth\Courier\ForgotController@forgot')->name('courier.forgot.submit');
        Route::get('/change-password/{token}', 'Auth\Courier\ForgotController@showChangePassForm')->name('courier.change.token');
        Route::post('/change-password', 'Auth\Courier\ForgotController@changepass')->name('courier.change.password');

        //------------ COURIER FORGOT SECTION ENDS ------------

        Route::get('/logout', 'Courier\LoginController@logout')->name('courier-logout');
        Route::get('/dashboard', 'Courier\CourierController@index')->name('courier-dashboard');

        Route::get('/profile', 'Courier\CourierController@profile')->name('courier-profile');
        Route::post('/profile', 'Courier\CourierController@profileupdate')->name('courier-profile-update');

        Route::get('/service/area', 'Courier\CourierController@serviceArea')->name('courier-service-area');
        Route::get('/service/area/create', 'Courier\CourierController@serviceAreaCreate')->name('courier-service-area-create');
        Route::post('/service/area/create', 'Courier\CourierController@serviceAreaStore')->name('courier-service-area-store');
        Route::get('/service/area/edit/{id}', 'Courier\CourierController@serviceAreaEdit')->name('courier-service-area-edit');
        Route::post('/service/area/edit/{id}', 'Courier\CourierController@serviceAreaUpdate')->name('courier-service-area-update');
        Route::get('/service/area/delete/{id}', 'Courier\CourierController@serviceAreaDestroy')->name('courier-service-area-delete');

        Route::get('/withdraw', 'Courier\WithdrawController@index')->name('courier-wwt-index');
        Route::get('/withdraw/create', 'Courier\WithdrawController@create')->name('courier-wwt-create');
        Route::post('/withdraw/create', 'Courier\WithdrawController@store')->name('courier-wwt-store');

        Route::get('my/purchases', 'Courier\CourierController@orders')->name('courier-purchases');
        Route::get('purchase/details/{id}', 'Courier\CourierController@orderDetails')->name('courier-purchase-details');
        Route::get('purchase/delivery/accept/{id}', 'Courier\CourierController@orderAccept')->name('courier-purchase-delivery-accept');
        Route::get('purchase/delivery/reject/{id}', 'Courier\CourierController@orderReject')->name('courier-purchase-delivery-reject');
        Route::get('purchase/delivery/complete/{id}', 'Courier\CourierController@orderComplete')->name('courier-purchase-delivery-complete');

        Route::get('/reset', 'Courier\CourierController@resetform')->name('courier-reset');
        Route::post('/reset', 'Courier\CourierController@reset')->name('courier-reset-submit');
    });

    // ************************************ COURIER SECTION ENDS**********************************************

    // ************************************ FRONT SECTION **********************************************


    Route::post('/item/report', 'Front\CatalogController@report')->name('catalog-item.report');

    Route::get('/', 'Front\FrontendController@index')->name('front.index');
    Route::get('/view', 'Front\CartController@view_cart')->name('front.cart-view');
    // Route removed - extraIndex merged into index() with section-based rendering

    Route::get('/currency/{id}', 'Front\FrontendController@currency')->name('front.currency');
    Route::get('/language/{id}', 'Front\FrontendController@language')->name('front.language');
    Route::get('/purchase/track/{id}', 'Front\FrontendController@trackload')->name('front.track.search');

    // SHIPMENT TRACKING SECTION
    Route::get('/tracking', 'Front\ShipmentTrackingController@index')->name('front.tracking');
    Route::get('/tracking/status', 'Front\ShipmentTrackingController@getStatus')->name('front.tracking.status');
    Route::get('/tracking/refresh', 'Front\ShipmentTrackingController@refresh')->name('front.tracking.refresh');
    Route::get('/my-shipments', 'Front\ShipmentTrackingController@myShipments')->name('front.my-shipments');
    // SHIPMENT TRACKING SECTION ENDS

    // PUBLICATION SECTION
    Route::get('/publications', 'Front\FrontendController@publications')->name('front.publications');
    Route::get('/publications/{slug}', 'Front\FrontendController@publicationshow')->name('front.publicationshow');
    Route::get('/publications/category/{slug}', 'Front\FrontendController@publicationcategory')->name('front.publicationcategory');
    Route::get('/publications/tag/{slug}', 'Front\FrontendController@publicationtags')->name('front.publicationtags');
    Route::get('/publication-search', 'Front\FrontendController@publicationsearch')->name('front.publicationsearch');
    Route::get('/publications/archive/{slug}', 'Front\FrontendController@publicationarchive')->name('front.publicationarchive');
    // PUBLICATION SECTION ENDS

    // HELP ARTICLE SECTION
    Route::get('/help-article', 'Front\FrontendController@helpArticle')->name('front.help-article');
    // HELP ARTICLE SECTION ENDS

    // CONTACT SECTION
    Route::get('/contact', 'Front\FrontendController@contact')->name('front.contact');
    Route::post('/contact', 'Front\FrontendController@contactemail')->name('front.contact.submit');
    Route::get('/contact/refresh_code', 'Front\FrontendController@refresh_code');
    // CONTACT SECTION  ENDS

 
    // CATALOG ITEM AUTO SEARCH SECTION
    Route::get('/autosearch/catalog-item/{slug}', 'Front\FrontendController@autosearch');
    // CATALOG ITEM AUTO SEARCH SECTION ENDS

    // CATEGORY SECTION
    Route::get('/categories', 'Front\CatalogController@categories')->name('front.categories');

    // NEW: Unified catalog tree with recursive category traversal
    // Shows all items from selected category AND all descendants
    // UNIFIED: 5-level category route
    // Structure: /category/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}
    // - brand = Brand slug (e.g., "nissan")
    // - catalog = Catalog slug (e.g., "safari-patrol-1997")
    // - cat1/cat2/cat3 = NewCategory slugs (levels 1, 2, 3)
    Route::get('/category/{category?}/{subcategory?}/{childcategory?}/{cat2?}/{cat3?}', 'Front\CatalogController@category')->name('front.category');

    // AJAX APIs for category selector (lightweight on-demand loading)
    Route::get('/api/category/catalogs', 'Front\CatalogController@getCatalogs')->name('front.api.catalogs');
    Route::get('/api/category/tree', 'Front\CatalogController@getTreeCategories')->name('front.api.tree');
    // CATEGORY SECTION ENDS

    // TAG SECTION
    Route::get('/tag/{slug}', 'Front\CatalogController@tag')->name('front.tag');
    // TAG SECTION ENDS

    // TAG SECTION
    Route::get('/search', 'Front\CatalogController@search')->name('front.search');
    // TAG SECTION ENDS

    // COMPARE SECTION
    Route::get('/item/compare/view', 'Front\CompareController@compare')->name('catalog-item.compare');
    Route::get('/compare/add/merchant/{merchantItemId}', 'Front\CompareController@addMerchantCompare')->name('merchant.compare.add');
    Route::get('/compare/remove/merchant/{merchantItemId}', 'Front\CompareController@removeMerchantCompare')->name('merchant.compare.remove');
    Route::get('/item/compare/add/merchant/{merchantItemId}', 'Front\CompareController@addcompare')->name('catalog-item.compare.add.merchant');
    Route::get('/item/compare/remove/{merchantItemId}', 'Front\CompareController@removecompare')->name('catalog-item.compare.remove');
    Route::get('/item/compare/add/{id}', 'Front\CompareController@addcompareLegacy')->name('catalog-item.compare.add');
    // COMPARE SECTION ENDS

    // CATALOG ITEM SECTION
    Route::get(
        '/item/{slug}/store/{merchant_id}/merchant_items/{merchant_item_id}',
        'Front\CatalogItemDetailsController@showByMerchantItem'
    )->whereNumber('merchant_id')->whereNumber('merchant_item_id')
    ->name('front.catalog-item');

    Route::get('/item/{slug}/{user}', 'Front\CatalogItemDetailsController@showByUser')
        ->name('front.catalog-item.user');

    Route::get('/item/{slug}', 'Front\CatalogItemDetailsController@show')
        ->name('front.catalog-item.legacy');

    Route::get('/item/{slug}/{merchant_item_id}', 'Front\CatalogItemDetailsController@showByMerchantItem')
         ->whereNumber('merchant_item_id')
         ->name('front.catalog-item.short');

    Route::get('/item/{slug}/{user}/{brand_quality_id}', 'Front\CatalogItemDetailsController@showByUserQuality')
         ->whereNumber('user')->whereNumber('brand_quality_id')
         ->name('front.catalog-item.user_quality');

    Route::get('/item/show/cross/{id}', 'Front\CatalogItemDetailsController@showCrossCatalogItem')->name('front.show.cross.catalog-item');
    Route::get('/afbuy/{slug}', 'Front\CatalogItemDetailsController@affCatalogItemRedirect')->name('affiliate.catalog-item');
    Route::get('/item/quick/view/{id}/', 'Front\CatalogItemDetailsController@quick')->name('catalog-item.quick');
    Route::post('/item/review', 'Front\CatalogItemDetailsController@reviewsubmit')->name('front.catalog-item.review.submit');
    Route::get('/item/view/review/{id}', 'Front\CatalogItemDetailsController@reviews')->name('front.catalog-item.reviews');
    Route::get('/item/view/side/review/{id}', 'Front\CatalogItemDetailsController@sideReviews')->name('front.catalog-item.side.reviews');
    // CATALOG ITEM SECTION ENDS

    // BUYER NOTE SECTION
    Route::post('/item/buyer-note/store', 'Front\CatalogItemDetailsController@buyerNoteStore')->name('catalog-item.buyer-note');
    Route::post('/item/buyer-note/edit/{id}', 'Front\CatalogItemDetailsController@buyerNoteEdit')->name('catalog-item.buyer-note.edit');
    Route::get('/item/buyer-note/delete/{id}', 'Front\CatalogItemDetailsController@buyerNoteDelete')->name('catalog-item.buyer-note.delete');
    // BUYER NOTE SECTION ENDS

    // REPLY SECTION
    Route::post('/item/reply/{id}', 'Front\CatalogItemDetailsController@reply')->name('catalog-item.reply');
    Route::post('/item/reply/edit/{id}', 'Front\CatalogItemDetailsController@replyedit')->name('catalog-item.reply.edit');
    Route::get('/item/reply/delete/{id}', 'Front\CatalogItemDetailsController@replydelete')->name('catalog-item.reply.delete');
    // REPLY SECTION ENDS

    // ============ UNIFIED CART SYSTEM (v3) ============
    // Single endpoint for ALL cart add operations
    // Uses merchant_item_id EXCLUSIVELY - NO fallbacks
    Route::post('/cart/unified', 'Front\CartController@unifiedAdd')->name('cart.unified.add');
    Route::get('/cart/unified', 'Front\CartController@unifiedAdd')->name('cart.unified.add.get'); // For legacy GET requests

    // CART SECTION
    Route::get('/carts/view', 'Front\CartController@cartview');
    Route::get('/carts', 'Front\CartController@cart')->name('front.cart');

    // Cart summary endpoint (AJAX only)
    Route::get('/cart/summary', 'Front\CartController@cartSummary')->name('cart.summary');

    // Increase/Decrease item quantity
    Route::post('/cart/increase', 'Front\CartController@increaseItem')->name('cart.increase');
    Route::post('/cart/decrease', 'Front\CartController@decreaseItem')->name('cart.decrease');
    Route::get('/cart/increase', 'Front\CartController@increaseItem')->name('cart.increase.get');
    Route::get('/cart/decrease', 'Front\CartController@decreaseItem')->name('cart.decrease.get');

    // Remove item
    Route::get('/removecart/{id}', 'Front\CartController@removecart')->name('cart.remove');

    // ============ CART ADD ROUTES ============
    // PRIMARY: Use POST /cart/unified with merchant_item_id for all cart additions
    Route::get('/cart/add/merchant/{merchantItemId}', 'Front\CartController@addMerchantCart')->name('merchant.cart.add');

    // DEPRECATED (return 410 Gone): These routes should NOT be used
    // All cart add functionality should use POST /cart/unified with merchant_item_id
    Route::get('/addcart/{id}', 'Front\CartController@addcart')->name('catalog-item.cart.add');          // DEPRECATED
    Route::get('/addtocart/{id}', 'Front\CartController@addtocart')->name('catalog-item.cart.quickadd'); // DEPRECATED
    Route::get('/addnumcart', 'Front\CartController@addnumcart')->name('details.cart');             // DEPRECATED
    Route::get('/addtonumcart', 'Front\CartController@addtonumcart');                               // DEPRECATED

    // ACTIVE: Cart quantity management routes
    Route::get('/addbyone', 'Front\CartController@addbyone');
    Route::get('/reducebyone', 'Front\CartController@reducebyone');
    // ============ END CART ROUTES ============
    Route::get('/upcolor', 'Front\CartController@upcolor');
    Route::get('/carts/discount-code', 'Front\DiscountCodeController@discountCodeCheck');
    // CART SECTION ENDS

    // FAVORITE SECTION
    Route::middleware('auth')->group(function () {
        Route::get('/favorite/add/merchant/{merchantItemId}', 'User\FavoriteController@addMerchantFavorite')->name('merchant.favorite.add');
        Route::get('/favorite/remove/merchant/{merchantItemId}', 'User\FavoriteController@removeMerchantFavorite')->name('merchant.favorite.remove');
    });
    // FAVORITE SECTION ENDS

    // CHECKOUT SECTION
    Route::get('/buy-now/{id}', 'Front\CheckoutController@buynow')->name('front.buynow');

    // ====================================================================
    // VENDOR CHECKOUT POLICY (STRICT)
    // ====================================================================
    // ALL checkout operations MUST have explicit merchant_id in Route.
    // NO session, NO POST, NO hidden inputs for merchant context.
    // Cart is multi-merchant; Checkout is single-merchant per transaction.
    // ====================================================================

    // Merchant-specific checkout routes (with session preservation middleware)
    Route::middleware(['preserve.session'])->prefix('checkout/merchant/{merchantId}')->group(function () {
        // Step 1: Address
        Route::get('/', 'Front\CheckoutController@checkoutMerchant')->name('front.checkout.merchant');
        Route::post('/step1/submit', 'Front\CheckoutController@checkoutMerchantStep1')->name('front.checkout.merchant.step1.submit');
        Route::get('/step1/submit', function($merchantId) {
            return redirect()->route('front.checkout.merchant', $merchantId)->with('info', __('Please fill out the form and submit again.'));
        });

        // Step 2: Shipping
        Route::get('/step2', 'Front\CheckoutController@checkoutMerchantStep2')->name('front.checkout.merchant.step2');
        Route::post('/step2/submit', 'Front\CheckoutController@checkoutMerchantStep2Submit')->name('front.checkout.merchant.step2.submit');
        Route::get('/step2/submit', function($merchantId) {
            return redirect()->route('front.checkout.merchant.step2', $merchantId)->with('info', __('Please fill out the form and submit again.'));
        });

        // Step 3: Payment
        Route::get('/step3', 'Front\CheckoutController@checkoutMerchantStep3')->name('front.checkout.merchant.step3');

        // ================================================================
        // PAYMENT ROUTES - All inside merchant context
        // ================================================================

        // MyFatoorah
        Route::post('/payment/myfatoorah', 'App\Http\Controllers\MyFatoorahController@index')->name('front.checkout.merchant.myfatoorah.submit');

        // Cash On Delivery
        Route::post('/payment/cod', 'Payment\Checkout\CashOnDeliveryController@store')->name('front.checkout.merchant.cod.submit');

        // Paypal
        Route::post('/payment/paypal', 'Payment\Checkout\PaypalController@store')->name('front.checkout.merchant.paypal.submit');

        // Stripe
        Route::post('/payment/stripe', 'Payment\Checkout\StripeController@store')->name('front.checkout.merchant.stripe.submit');

        // Wallet
        Route::post('/payment/wallet', 'Payment\Checkout\WalletPaymentController@store')->name('front.checkout.merchant.wallet.submit');

        // Manual
        Route::post('/payment/manual', 'Payment\Checkout\ManualPaymentController@store')->name('front.checkout.merchant.manual.submit');

        // Instamojo
        Route::post('/payment/instamojo', 'Payment\Checkout\InstamojoController@store')->name('front.checkout.merchant.instamojo.submit');

        // Paystack
        Route::post('/payment/paystack', 'Payment\Checkout\PaystackController@store')->name('front.checkout.merchant.paystack.submit');

        // PayTM
        Route::post('/payment/paytm', 'Payment\Checkout\PaytmController@store')->name('front.checkout.merchant.paytm.submit');

        // Mollie
        Route::post('/payment/mollie', 'Payment\Checkout\MollieController@store')->name('front.checkout.merchant.mollie.submit');

        // RazorPay
        Route::post('/payment/razorpay', 'Payment\Checkout\RazorpayController@store')->name('front.checkout.merchant.razorpay.submit');

        // Authorize.Net
        Route::post('/payment/authorize', 'Payment\Checkout\AuthorizeController@store')->name('front.checkout.merchant.authorize.submit');

        // Mercadopago
        Route::post('/payment/mercadopago', 'Payment\Checkout\MercadopagoController@store')->name('front.checkout.merchant.mercadopago.submit');

        // Flutter Wave
        Route::post('/payment/flutterwave', 'Payment\Checkout\FlutterwaveController@store')->name('front.checkout.merchant.flutterwave.submit');

        // SSLCommerz
        Route::post('/payment/ssl', 'Payment\Checkout\SslController@store')->name('front.checkout.merchant.ssl.submit');

        // Voguepay
        Route::post('/payment/voguepay', 'Payment\Checkout\VoguepayController@store')->name('front.checkout.merchant.voguepay.submit');

        // Location reset
        Route::post('/location/reset', 'Front\CheckoutController@resetLocation')->name('front.checkout.merchant.location.reset');

        // Discount Code (merchant-specific)
        Route::get('/discount-code/check', 'Front\DiscountCodeController@discountCodeCheck')->name('front.checkout.merchant.discount-code.check');
        Route::post('/discount-code/remove', 'Front\DiscountCodeController@removeDiscountCode')->name('front.checkout.merchant.discount-code.remove');

        // Wallet check
        Route::get('/wallet-check', 'Front\CheckoutController@walletcheck')->name('front.checkout.merchant.wallet.check');
    });

    // ====================================================================
    // GEOCODING ROUTES (Inside session middleware)
    // ====================================================================
    Route::middleware(['preserve.session'])->prefix('geocoding')->group(function () {
        Route::post('/reverse', [\App\Http\Controllers\Api\GeocodingController::class, 'reverseGeocode'])->name('geocoding.reverse');
        Route::post('/reverse-with-sync', [\App\Http\Controllers\GeocodingController::class, 'reverseGeocodeWithSync'])->name('geocoding.reverse-with-sync');
        Route::post('/tax-from-coordinates', [\App\Http\Controllers\GeocodingController::class, 'getTaxFromCoordinates'])->name('geocoding.tax');
        Route::get('/search-cities', [\App\Http\Controllers\Api\GeocodingController::class, 'searchCities'])->name('geocoding.search');
        Route::post('/sync-country', [\App\Http\Controllers\Api\GeocodingController::class, 'startCountrySync'])->name('geocoding.sync');
        Route::get('/sync-progress', [\App\Http\Controllers\Api\GeocodingController::class, 'getSyncProgress'])->name('geocoding.progress');
    });

    // ====================================================================
    // PAYMENT NOTIFY/CALLBACK ROUTES (External - no merchant_id)
    // These are called by payment gateways, not by our app
    // ====================================================================
    Route::get('/checkout/payment/myfatoorah/notify', 'App\Http\Controllers\MyFatoorahController@notify')->name('front.myfatoorah.notify');
    Route::get('/checkout/payment/paypal-notify', 'Payment\Checkout\PaypalController@notify')->name('front.paypal.notify');
    Route::get('/payment/stripe/notify', 'Payment\Checkout\StripeController@notify')->name('front.stripe.notify');
    Route::get('/checkout/payment/instamojo-notify', 'Payment\Checkout\InstamojoController@notify')->name('front.instamojo.notify');
    Route::post('/checkout/payment/paytm-notify', 'Payment\Checkout\PaytmController@notify')->name('front.paytm.notify');
    Route::get('/checkout/payment/molly-notify', 'Payment\Checkout\MollieController@notify')->name('front.molly.notify');
    Route::post('/checkout/payment/razorpay-notify', 'Payment\Checkout\RazorpayController@notify')->name('front.razorpay.notify');
    Route::post('/checkout/payment/ssl-notify', 'Payment\Checkout\SslController@notify')->name('front.ssl.notify');

    // Payment return/cancel (after external redirect)
    Route::get('/checkout/payment/return', 'Front\CheckoutController@payreturn')->name('front.payment.return');
    Route::get('/checkout/payment/cancle', 'Front\CheckoutController@paycancle')->name('front.payment.cancle');

    // ====================================================================
    // REGULAR CHECKOUT - COMPLETELY DISABLED
    // Redirect ALL non-vendor checkout to cart with error
    // ====================================================================
    Route::get('/checkout', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('يرجى اختيار تاجر للمتابعة مع الدفع.'));
    })->name('front.checkout');
    Route::any('/checkout/step1/submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('يرجى اختيار تاجر للمتابعة.'));
    })->name('front.checkout.step1.submit');
    Route::get('/checkout/step2', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('يرجى اختيار تاجر للمتابعة.'));
    })->name('front.checkout.step2');
    Route::any('/checkout/step2/submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('يرجى اختيار تاجر للمتابعة.'));
    })->name('front.checkout.step2.submit');
    Route::get('/checkout/step3', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('يرجى اختيار تاجر للمتابعة.'));
    })->name('front.checkout.step3');

    // ====================================================================
    // OLD PAYMENT ROUTES - DISABLED (Redirect to cart)
    // Payment without vendor context is not allowed
    // ====================================================================
    Route::post('/checkout/payment/myfatoorah/submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.myfatoorah.submit');
    Route::post('/checkout/payment/cod-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.cod.submit');
    Route::post('/checkout/payment/paypal/submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.paypal.submit');
    Route::post('/checkout/payment/stripe-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.stripe.submit');
    Route::post('/checkout/payment/wallet-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.wallet.submit');
    Route::post('/checkout/payment/manual-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.manual.submit');
    Route::post('/checkout/payment/instamojo-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.instamojo.submit');
    Route::post('/checkout/payment/paystack-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.paystack.submit');
    Route::post('/checkout/payment/paytm-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.paytm.submit');
    Route::post('/checkout/payment/molly-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.molly.submit');
    Route::post('/checkout/payment/razorpay-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.razorpay.submit');
    Route::post('/checkout/payment/authorize-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.authorize.submit');
    Route::post('/checkout/payment/mercadopago-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.mercadopago.submit');
    Route::post('/checkout/payment/flutter-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.flutter.submit');
    Route::post('/checkout/payment/ssl-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.ssl.submit');
    Route::post('/checkout/payment/voguepay-submit', function() {
        return redirect()->route('front.cart')->with('unsuccess', __('خطأ: لم يتم تحديد التاجر.'));
    })->name('front.voguepay.submit');

    // Discount Code routes (global - for cart page)
    Route::get('/carts/discount-code/check', 'Front\DiscountCodeController@discountCodeCheck')->name('front.discount-code.check');
    Route::post('/carts/discount-code/remove', 'Front\DiscountCodeController@removeDiscountCode')->name('front.discount-code.remove');

    // CSRF Token refresh endpoint
    Route::get('/csrf-token', function() {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.token');

    // Tryoto Webhook (external - no auth)
    Route::post('/webhooks/tryoto', 'App\Http\Controllers\TryotoWebhookController@handle')->name('webhooks.tryoto');
    Route::get('/webhooks/tryoto/test', 'App\Http\Controllers\TryotoWebhookController@test')->name('webhooks.tryoto.test');

    // Flutterwave Notify Routes

    // TopUp
    Route::post('/dflutter/notify', 'Payment\TopUp\FlutterwaveController@notify')->name('topup.flutter.notify');

    // Membership Plan
    Route::post('/uflutter/notify', 'Payment\MembershipPlan\FlutterwaveController@notify')->name('user.flutter.notify');

    // Checkout
    Route::post('/cflutter/notify', 'Payment\Checkout\FlutterwaveController@notify')->name('front.flutter.notify');

    // CHECKOUT SECTION ENDS

    //   Mobile Checkout section

    Route::get('/payment/checkout', 'Api\Payment\CheckoutController@checkout')->name('payment.checkout');
    Route::post('/payment/stripe-submit', 'Api\Payment\StripeController@store')->name('payment.stripe');
    Route::get('/payment/stripe-notify', 'Api\Payment\StripeController@notify')->name('payment.notify');

    Route::get('/topup/app/payment/{slug1}/{slug2}', 'Api\Payment\CheckoutController@topuploadpayment')->name('topup.app.payment');

    Route::get('/checkout/payment/{slug1}/{slug2}', 'Front\CheckoutController@loadpayment')->name('front.load.payment');

    // Note: Flutter Wave routes moved to Api\Payment section below
    // Route::post('/api/flutter/submit', 'Api\Payment\FlutterWaveController@store')->name('api.flutter.submit');
    // Route::post('/flutter/notify', 'Api\Payment\FlutterWaveController@notify')->name('api.flutter.notify');

    Route::get('/payment/successfull/{get}', 'Front\FrontendController@success')->name('front.payment.success');

    Route::post('/api/cod/submit', 'Api\Payment\CashOnDeliveryController@store')->name('api.cod.submit');
    Route::post('/api/wallet/submit', 'Api\Payment\WalletController@store')->name('api.wallet.submit');
    Route::post('/api/manual/submit', 'Api\Payment\ManualController@store')->name('api.manual.submit');

    Route::post('/api/paystack/submit', 'Api\Payment\PaystackController@store')->name('api.paystack.submit');

    Route::post('/api/instamojo/submit', 'Api\Payment\InstamojoController@store')->name('api.instamojo.submit');

    Route::get('/api/checkout/instamojo/notify', 'Api\Payment\InstamojoController@notify')->name('api.instamojo.notify');

    //flutter
    Route::post('/api/flutter/submit', 'Api\Payment\FlutterWaveController@store')->name('api.flutter.submit');
    Route::post('/api/flutter/notify', 'Api\Payment\FlutterWaveController@notify')->name('api.payment.flutter.notify');

    // ssl Routes
    Route::post('/api/ssl/submit', 'Api\Payment\SslController@store')->name('api.ssl.submit');
    Route::post('/api/ssl/notify', 'Api\Payment\SslController@notify')->name('api.ssl.notify');
    Route::post('/api/ssl/cancle', 'Api\Payment\SslController@cancle')->name('api.ssl.cancle');
    Route::get('/topup/payment/{number}', 'Api\User\TopUpController@sendTopUp')->name('user.topup.send');

    // Paypal
    Route::post('/checkout/payment/paypal-submit', 'Api\Payment\PaypalController@store')->name('api.paypal.submit');
    Route::get('/api/checkout/paypal/notify', 'Api\Payment\PaypalController@notify')->name('api.paypal.notify');
    Route::get('/api/checkout/payment/return', 'Api\Payment\PaypalController@payreturn')->name('api.paypal.return');
    Route::get('/api/checkout/payment/cancle', 'Api\Payment\PaypalController@paycancle')->name('api.paypal.cancle');

    Route::post('/api/payment/stripe-submit', 'Api\Payment\StripeController@store')->name('api.stripe.submit');

    // Molly Routes
    Route::post('/api/molly/submit', 'Api\Payment\MollyController@store')->name('api.molly.submit');
    Route::get('/api/molly/notify', 'Api\Payment\MollyController@notify')->name('api.molly.notify');

    //PayTM Routes
    Route::post('/api/paytm-submit', 'Api\Payment\PaytmController@store')->name('api.paytm.submit');;
    Route::post('/api/paytm-callback', 'Api\Payment\PaytmController@paytmCallback')->name('api.paytm.notify');

    Route::post('/api/authorize-submit', 'Api\Payment\AuthorizeController@store')->name('api.authorize.submit');

    //RazorPay Routes
    Route::post('/api/razorpay-submit', 'Api\Payment\RazorpayController@store')->name('api.razorpay.submit');;
    Route::post('/api/razorpay-callback', 'Api\Payment\RazorpayController@razorCallback')->name('api.razorpay.notify');

    //   Mobile Checkout section

    // Mercadopago Routes
    Route::get('/api/checkout/mercadopago/return', 'Api\Payment\MercadopagoController@payreturn')->name('api.mercadopago.return');
    Route::post('/api/checkout/mercadopago/notify', 'Api\Payment\MercadopagoController@notify')->name('api.mercadopago.notify');
    Route::post('/api/checkout/mercadopago/submit', 'Api\Payment\MercadopagoController@store')->name('api.mercadopago.submit');

    // MERCHANT SECTION

    Route::post('/merchant/contact', 'Front\MerchantController@merchantcontact')->name('front.merchant.contact');

    // MERCHANT SECTION ENDS

    // SUBSCRIBE SECTION

    Route::post('/subscriber/store', 'Front\FrontendController@subscribe')->name('front.subscribe');

    // SUBSCRIBE SECTION ENDS

    // LOGIN WITH FACEBOOK OR GOOGLE SECTION
    Route::get('auth/{provider}', 'Auth\User\SocialRegisterController@redirectToProvider')->name('social-provider');
    Route::get('auth/{provider}/callback', 'Auth\User\SocialRegisterController@handleProviderCallback');
    // LOGIN WITH FACEBOOK OR GOOGLE SECTION ENDS

    //  CRONJOB

    Route::get('/vendor/subscription/check', 'Front\FrontendController@subcheck');

    // CRONJOB ENDS

    Route::post('the/muaadh/ocean/2441139', 'Front\FrontendController@subscription');
    Route::get('finalize', 'Front\FrontendController@finalize');
    Route::get('update-finalize', 'Front\FrontendController@updateFinalize');

    // MERCHANT AND PAGE SECTION
    Route::get('/country/tax/check', 'Front\CartController@country_tax');
    Route::get('/{slug}', 'Front\MerchantController@index')->name('front.merchant');

    // MERCHANT AND PAGE SECTION ENDS

    // ************************************ FRONT SECTION ENDS**********************************************

});




Route::group(['prefix' => 'tryoto'], function () {
    Route::get('set-webhook', [TryOtoController::class, 'setWebhook'])->name('tryoto.set-webhook');
    Route::post('webhook/callback', [TryOtoController::class, 'listenWebhook'])->name('tryoto.callback');

    // Debug route for testing Tryoto API
    Route::get('test-api', function () {
        $refreshToken = config('services.tryoto.sandbox')
            ? config('services.tryoto.test.token')
            : config('services.tryoto.live.token');

        $url = config('services.tryoto.sandbox')
            ? config('services.tryoto.test.url')
            : config('services.tryoto.live.url');

        $output = ['step1_authorize' => []];

        // Step 1: Get access token
        $response = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->post($url . '/rest/v2/refreshToken', ['refresh_token' => $refreshToken]);
        $output['step1_authorize']['status'] = $response->status();
        $output['step1_authorize']['body'] = $response->json();

        if (!$response->successful()) {
            return response()->json($output, 500);
        }

        $token = $response->json()['access_token'];

        // Step 2: Test checkOTODeliveryFee
        $output['step2_check_fee'] = [];
        $feeResponse = Http::withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->withToken($token)->post($url . '/rest/v2/checkOTODeliveryFee', [
            'originCity' => 'Riyadh',
            'destinationCity' => 'Riyadh',
            'weight' => 100,
            'xlength' => 30,
            'xheight' => 30,
            'xwidth' => 30
        ]);

        $output['step2_check_fee']['status'] = $feeResponse->status();
        $output['step2_check_fee']['body'] = $feeResponse->json();

        return response()->json($output);
    })->name('tryoto.test-api');
});


Route::post('the/muaadh/ocean/2441139', 'Front\FrontendController@subscription');
Route::get('finalize', 'Front\FrontendController@finalize');


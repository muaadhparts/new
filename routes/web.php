<?php

// ************************************ ADMIN SECTION **********************************************

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

//     Artisan::call('products:update-price');
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
//     Artisan::call('products:update-price');
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
//     Artisan::call('products:update-price');
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
    Route::get('/catalog-item/sku/{sku}',      [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.sku');
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

Route::prefix('admin')->group(function () {

    //------------ ADMIN LOGIN SECTION ------------

    Route::get('/login', 'Auth\Admin\LoginController@showForm')->name('admin.login');
    Route::post('/login', 'Auth\Admin\LoginController@login')->name('admin.login.submit');
    Route::get('/logout', 'Auth\Admin\LoginController@logout')->name('admin.logout');

    //------------ ADMIN LOGIN SECTION ENDS ------------

    //------------ ADMIN FORGOT SECTION ------------

    Route::get('/forgot', 'Auth\Admin\ForgotController@showForm')->name('admin.forgot');
    Route::post('/forgot', 'Auth\Admin\ForgotController@forgot')->name('admin.forgot.submit');
    Route::get('/change-password/{token}', 'Auth\Admin\ForgotController@showChangePassForm')->name('admin.change.token');
    Route::post('/change-password', 'Auth\Admin\ForgotController@changepass')->name('admin.change.password');

    //------------ ADMIN FORGOT SECTION ENDS ------------

    //------------ PROTECTED ADMIN ROUTES (Require Authentication) ------------
    Route::middleware(['auth:admin'])->group(function () {

        //------------ ADMIN NOTIFICATION SECTION ------------
        Route::get('/all/notf/count', 'Admin\NotificationController@all_notf_count')->name('all-notf-count');
        Route::get('/user/notf/show', 'Admin\NotificationController@user_notf_show')->name('user-notf-show');
        Route::get('/user/notf/clear', 'Admin\NotificationController@user_notf_clear')->name('user-notf-clear');
        Route::get('/purchase/notf/show', 'Admin\NotificationController@purchase_notf_show')->name('purchase-notf-show');
        Route::get('/purchase/notf/clear', 'Admin\NotificationController@purchase_notf_clear')->name('purchase-notf-clear');
        Route::get('/catalog-item/notf/show', 'Admin\NotificationController@catalog_item_notf_show')->name('catalog-item-notf-show');
        Route::get('/catalog-item/notf/clear', 'Admin\NotificationController@catalog_item_notf_clear')->name('catalog-item-notf-clear');
        Route::get('/conv/notf/show', 'Admin\NotificationController@conv_notf_show')->name('conv-notf-show');
        Route::get('/conv/notf/clear', 'Admin\NotificationController@conv_notf_clear')->name('conv-notf-clear');
        //------------ ADMIN NOTIFICATION SECTION ENDS ------------

        //------------ ADMIN DASHBOARD & PROFILE SECTION ------------
        Route::get('/', 'Admin\DashboardController@index')->name('admin.dashboard');
        Route::get('/profile', 'Admin\DashboardController@profile')->name('admin.profile');
        Route::post('/profile/update', 'Admin\DashboardController@profileupdate')->name('admin.profile.update');
        Route::get('/password', 'Admin\DashboardController@passwordreset')->name('admin.password');
        Route::post('/password/update', 'Admin\DashboardController@changepass')->name('admin.password.update');
        //------------ ADMIN DASHBOARD & PROFILE SECTION ENDS ------------

        //------------ ADMIN PERFORMANCE MONITORING SECTION ------------
        Route::get('/performance', 'Admin\PerformanceController@index')->name('admin-performance');
        Route::get('/performance/slow-queries', 'Admin\PerformanceController@slowQueries')->name('admin-performance-slow-queries');
        Route::get('/performance/slow-requests', 'Admin\PerformanceController@slowRequests')->name('admin-performance-slow-requests');
        Route::get('/performance/repeated-queries', 'Admin\PerformanceController@repeatedQueries')->name('admin-performance-repeated-queries');
        Route::get('/performance/report', 'Admin\PerformanceController@downloadReport')->name('admin-performance-report');
        Route::get('/performance/api/summary', 'Admin\PerformanceController@apiSummary')->name('admin-performance-api-summary');
        Route::post('/performance/prune', 'Admin\PerformanceController@pruneOldEntries')->name('admin-performance-prune');
        //------------ ADMIN PERFORMANCE MONITORING SECTION ENDS ------------

        //------------ ADMIN API CREDENTIALS SECTION ------------
        Route::get('/credentials', 'Admin\ApiCredentialController@index')->name('admin.credentials.index');
        Route::get('/credentials/create', 'Admin\ApiCredentialController@create')->name('admin.credentials.create');
        Route::post('/credentials', 'Admin\ApiCredentialController@store')->name('admin.credentials.store');
        Route::get('/credentials/{id}/edit', 'Admin\ApiCredentialController@edit')->name('admin.credentials.edit');
        Route::put('/credentials/{id}', 'Admin\ApiCredentialController@update')->name('admin.credentials.update');
        Route::delete('/credentials/{id}', 'Admin\ApiCredentialController@destroy')->name('admin.credentials.destroy');
        Route::post('/credentials/{id}/toggle', 'Admin\ApiCredentialController@toggle')->name('admin.credentials.toggle');
        Route::post('/credentials/{id}/test', 'Admin\ApiCredentialController@test')->name('admin.credentials.test');
        //------------ ADMIN API CREDENTIALS SECTION ENDS ------------

        //------------ ADMIN MERCHANT CREDENTIALS SECTION ------------
        Route::get('/merchant-credentials', 'Admin\MerchantCredentialController@index')->name('admin.merchant-credentials.index');
        Route::get('/merchant-credentials/create', 'Admin\MerchantCredentialController@create')->name('admin.merchant-credentials.create');
        Route::post('/merchant-credentials', 'Admin\MerchantCredentialController@store')->name('admin.merchant-credentials.store');
        Route::get('/merchant-credentials/{id}/edit', 'Admin\MerchantCredentialController@edit')->name('admin.merchant-credentials.edit');
        Route::put('/merchant-credentials/{id}', 'Admin\MerchantCredentialController@update')->name('admin.merchant-credentials.update');
        Route::delete('/merchant-credentials/{id}', 'Admin\MerchantCredentialController@destroy')->name('admin.merchant-credentials.destroy');
        Route::post('/merchant-credentials/{id}/toggle', 'Admin\MerchantCredentialController@toggle')->name('admin.merchant-credentials.toggle');
        Route::post('/merchant-credentials/{id}/test', 'Admin\MerchantCredentialController@test')->name('admin.merchant-credentials.test');
        //------------ ADMIN MERCHANT CREDENTIALS SECTION ENDS ------------
    });

    //------------ ADMIN PURCHASE SECTION ------------

    Route::group(['middleware' => 'permissions:orders'], function () {

        Route::get('/purchases/datatables/{slug}', 'Admin\PurchaseController@datatables')->name('admin-purchase-datatables'); //JSON REQUEST
        Route::get('/purchases', 'Admin\PurchaseController@purchases')->name('admin-purchases-all');
        Route::get('/purchase/edit/{id}', 'Admin\PurchaseController@edit')->name('admin-purchase-edit');
        Route::post('/purchase/update/{id}', 'Admin\PurchaseController@update')->name('admin-purchase-update');
        Route::get('/purchase/{id}/show', 'Admin\PurchaseController@show')->name('admin-purchase-show');
        Route::get('/purchase/{id}/invoice', 'Admin\PurchaseController@invoice')->name('admin-purchase-invoice');
        Route::get('/purchase/{id}/print', 'Admin\PurchaseController@printpage')->name('admin-purchase-print');
        Route::get('/purchase/{id1}/status/{status}', 'Admin\PurchaseController@status')->name('admin-purchase-status');
        Route::post('/purchase/email/', 'Admin\PurchaseController@emailsub')->name('admin-purchase-emailsub');
        Route::post('/purchase/{id}/license', 'Admin\PurchaseController@license')->name('admin-purchase-license');
        Route::post('/purchase/product-submit', 'Admin\PurchaseController@product_submit')->name('admin-purchase-product-submit');
        Route::get('/purchase/product-show/{id}', 'Admin\PurchaseController@product_show');
        Route::get('/purchase/addcart/{id}', 'Admin\PurchaseController@addcart');
        Route::get('/purchasecart/product-edit/{id}/{itemid}/{purchaseid}', 'Admin\PurchaseController@product_edit')->name('admin-purchase-product-edit');
        Route::get('/purchase/updatecart/{id}', 'Admin\PurchaseController@updatecart');
        Route::get('/purchasecart/product-delete/{id}/{purchaseid}', 'Admin\PurchaseController@product_delete')->name('admin-purchase-product-delete');
        // Purchase Tracking

        // CREATE PURCHASE

        Route::get('/purchase/catalog-item/datatables', 'Admin\PurchaseCreateController@datatables')->name('admin-purchase-catalog-item-datatables');
        Route::get('/purchase/create', 'Admin\PurchaseCreateController@create')->name('admin-purchase-create');
        Route::get('/purchase/catalog-item/add/{catalog_item_id}', 'Admin\PurchaseCreateController@addProduct')->name('admin-purchase-catalog-item-add');
        Route::get('/purchase/catalog-item/add', 'Admin\PurchaseCreateController@purchaseStore')->name('admin.purchase.store.new');
        Route::get('/purchase/catalog-item/remove/{catalog_item_id}', 'Admin\PurchaseCreateController@removePurchaseProduct')->name('admin.purchase.catalog-item.remove');
        Route::get('/purchase/create/catalog-item-show/{id}', 'Admin\PurchaseCreateController@catalog_item_show');
        Route::get('/purchase/create/addcart/{id}', 'Admin\PurchaseCreateController@addcart');
        Route::get('/purchase/remove/addcart/{id}', 'Admin\PurchaseCreateController@removeCart')->name('admin.purchase.remove.cart');
        Route::get('/purchase/create/user-address', 'Admin\PurchaseCreateController@userAddress');
        Route::post('/purchase/create/user-address', 'Admin\PurchaseCreateController@userAddressSubmit')->name('admin.purchase.create.user.address');
        Route::post('/purchase/create/purchase/view', 'Admin\PurchaseCreateController@viewCreatePurchase')->name('admin.purchase.create.view');
        Route::get('/purchase/create/purchase/submit', 'Admin\PurchaseCreateController@CreatePurchaseSubmit')->name('admin-purchase-create-submit');

        Route::get('/purchase/{id}/timeline', 'Admin\PurchaseTimelineController@index')->name('admin-purchase-timeline');
        Route::get('/purchase/{id}/timelineload', 'Admin\PurchaseTimelineController@load')->name('admin-purchase-timeline-load');
        Route::post('/purchase/timeline/store', 'Admin\PurchaseTimelineController@store')->name('admin-purchase-timeline-store');
        Route::get('/purchase/timeline/add', 'Admin\PurchaseTimelineController@add')->name('admin-purchase-timeline-add');
        Route::get('/purchase/timeline/edit/{id}', 'Admin\PurchaseTimelineController@edit')->name('admin-purchase-timeline-edit');
        Route::post('/purchase/timeline/update/{id}', 'Admin\PurchaseTimelineController@update')->name('admin-purchase-timeline-update');
        Route::delete('/purchase/timeline/delete/{id}', 'Admin\PurchaseTimelineController@delete')->name('admin-purchase-timeline-delete');

        // Purchase Tracking Ends

    });

    //------------ ADMIN PURCHASE SECTION ENDS------------

    //------------ ADMIN SHIPMENTS SECTION ------------

    Route::group(['middleware' => 'permissions:orders'], function () {
        Route::get('/shipments', 'Admin\ShipmentController@index')->name('admin.shipments.index');
        Route::get('/shipments/show/{tracking}', 'Admin\ShipmentController@show')->name('admin.shipments.show');
        Route::get('/shipments/refresh/{tracking}', 'Admin\ShipmentController@refresh')->name('admin.shipments.refresh');
        Route::post('/shipments/cancel/{tracking}', 'Admin\ShipmentController@cancel')->name('admin.shipments.cancel');
        Route::get('/shipments/export', 'Admin\ShipmentController@export')->name('admin.shipments.export');
        Route::post('/shipments/bulk-refresh', 'Admin\ShipmentController@bulkRefresh')->name('admin.shipments.bulk-refresh');
        Route::get('/shipments/reports', 'Admin\ShipmentController@reports')->name('admin.shipments.reports');
    });

    //------------ ADMIN SHIPMENTS SECTION ENDS------------

    /////////////////////////////// ////////////////////////////////////////////

    // --------------- ADMIN COUNTRY & CITY SECTION (Protected) ---------------//
    Route::middleware(['auth:admin'])->group(function () {
        Route::get('/country/datatables', 'Admin\CountryController@datatables')->name('admin-country-datatables');
        Route::get('/manage/country', 'Admin\CountryController@manageCountry')->name('admin-country-index');
        Route::get('/manage/country/status/{id1}/{id2}', 'Admin\CountryController@status')->name('admin-country-status');
        Route::get('/country/delete/{id}', 'Admin\CountryController@delete')->name('admin-country-delete');
        Route::get('/country/tax/datatables', 'Admin\CountryController@taxDatatables')->name('admin-country-tax-datatables');
        Route::get('/manage/country/tax', 'Admin\CountryController@country_tax')->name('admin-country-tax');
        Route::get('/country/set-tax/{id}', 'Admin\CountryController@setTax')->name('admin-set-tax');
        Route::post('/country/set-tax/store/{id}', 'Admin\CountryController@updateTax')->name('admin-tax-update');

        Route::get('/city/datatables/{country}', 'Admin\CityController@datatables')->name('admin-city-datatables');
        Route::get('/manage/city/{country}', 'Admin\CityController@managecity')->name('admin-city-index');
        Route::get('/city/create/{country}', 'Admin\CityController@create')->name('admin-city-create');
        Route::post('/city/store/{country}', 'Admin\CityController@store')->name('admin-city-store');
        Route::get('/city/status/{id1}/{id2}', 'Admin\CityController@status')->name('admin-city-status');
        Route::get('/city/edit/{id}', 'Admin\CityController@edit')->name('admin-city-edit');
        Route::post('/city/update/{id}', 'Admin\CityController@update')->name('admin-city-update');
        Route::delete('/city/delete/{id}', 'Admin\CityController@delete')->name('admin-city-delete');
    });
    // --------------- ADMIN COUNTRY & CITY SECTION ENDS ---------------//

    //------------ ADMIN CATEGORY SECTION ENDS------------

    Route::group(['middleware' => 'permissions:earning'], function () {

        // -------------------------- Admin Total Income Route --------------------------//
        Route::get('tax/calculate', 'Admin\IncomeController@taxCalculate')->name('admin-tax-calculate-income');
        Route::get('subscription/earning', 'Admin\IncomeController@subscriptionIncome')->name('admin-subscription-income');
        Route::get('withdraw/earning', 'Admin\IncomeController@withdrawIncome')->name('admin-withdraw-income');
        Route::get('commission/earning', 'Admin\IncomeController@commissionIncome')->name('admin-commission-income');
        // -------------------------- Admin Total Income Route --------------------------//
    });

    /////////////////////////////// ////////////////////////////////////////////

    //------------ ADMIN MANAGE CATEGORY SECTION ------------

    Route::group(['middleware' => 'permissions:categories'], function () {

        Route::get('/category/datatables', 'Admin\CategoryController@datatables')->name('admin-cat-datatables'); //JSON REQUEST
        Route::get('/category', 'Admin\CategoryController@index')->name('admin-cat-index');
        Route::get('/category/create', 'Admin\CategoryController@create')->name('admin-cat-create');
        Route::post('/category/create', 'Admin\CategoryController@store')->name('admin-cat-store');
        Route::get('/category/edit/{id}', 'Admin\CategoryController@edit')->name('admin-cat-edit');
        Route::post('/category/edit/{id}', 'Admin\CategoryController@update')->name('admin-cat-update');
        Route::delete('/category/delete/{id}', 'Admin\CategoryController@destroy')->name('admin-cat-delete');
        Route::get('/category/featured/{id1}/{id2}', 'Admin\CategoryController@featured')->name('admin-cat-featured');
        Route::get('/category/status/{id1}/{id2}', 'Admin\CategoryController@status')->name('admin-cat-status');

        //------------ ADMIN ATTRIBUTE SECTION ------------

        Route::get('/attribute/datatables', 'Admin\AttributeController@datatables')->name('admin-attr-datatables'); //JSON REQUEST
        Route::get('/attribute', 'Admin\AttributeController@index')->name('admin-attr-index');
        Route::get('/attribute/{catid}/attrCreateForCategory', 'Admin\AttributeController@attrCreateForCategory')->name('admin-attr-createForCategory');
        Route::get('/attribute/{subcatid}/attrCreateForSubcategory', 'Admin\AttributeController@attrCreateForSubcategory')->name('admin-attr-createForSubcategory');
        Route::get('/attribute/{childcatid}/attrCreateForChildcategory', 'Admin\AttributeController@attrCreateForChildcategory')->name('admin-attr-createForChildcategory');
        Route::post('/attribute/store', 'Admin\AttributeController@store')->name('admin-attr-store');
        Route::get('/attribute/{id}/manage', 'Admin\AttributeController@manage')->name('admin-attr-manage');
        Route::get('/attribute/{attrid}/edit', 'Admin\AttributeController@edit')->name('admin-attr-edit');
        Route::post('/attribute/edit/{id}', 'Admin\AttributeController@update')->name('admin-attr-update');
        Route::get('/attribute/{id}/options', 'Admin\AttributeController@options')->name('admin-attr-options');
        Route::get('/attribute/delete/{id}', 'Admin\AttributeController@destroy')->name('admin-attr-delete');

        // SUBCATEGORY SECTION ------------

        Route::get('/subcategory/datatables', 'Admin\SubCategoryController@datatables')->name('admin-subcat-datatables'); //JSON REQUEST
        Route::get('/subcategory', 'Admin\SubCategoryController@index')->name('admin-subcat-index');
        Route::get('/subcategory/create', 'Admin\SubCategoryController@create')->name('admin-subcat-create');
        Route::post('/subcategory/create', 'Admin\SubCategoryController@store')->name('admin-subcat-store');
        Route::get('/subcategory/edit/{id}', 'Admin\SubCategoryController@edit')->name('admin-subcat-edit');
        Route::post('/subcategory/edit/{id}', 'Admin\SubCategoryController@update')->name('admin-subcat-update');
        Route::delete('/subcategory/delete/{id}', 'Admin\SubCategoryController@destroy')->name('admin-subcat-delete');
        Route::get('/subcategory/status/{id1}/{id2}', 'Admin\SubCategoryController@status')->name('admin-subcat-status');
        Route::get('/load/subcategories/{id}/', 'Admin\SubCategoryController@load')->name('admin-subcat-load'); //JSON REQUEST

        // SUBCATEGORY SECTION ENDS------------

        // CHILDCATEGORY SECTION ------------

        Route::get('/childcategory/datatables', 'Admin\ChildCategoryController@datatables')->name('admin-childcat-datatables'); //JSON REQUEST
        Route::get('/childcategory', 'Admin\ChildCategoryController@index')->name('admin-childcat-index');
        Route::get('/childcategory/create', 'Admin\ChildCategoryController@create')->name('admin-childcat-create');
        Route::post('/childcategory/create', 'Admin\ChildCategoryController@store')->name('admin-childcat-store');
        Route::get('/childcategory/edit/{id}', 'Admin\ChildCategoryController@edit')->name('admin-childcat-edit');
        Route::post('/childcategory/edit/{id}', 'Admin\ChildCategoryController@update')->name('admin-childcat-update');
        Route::delete('/childcategory/delete/{id}', 'Admin\ChildCategoryController@destroy')->name('admin-childcat-delete');
        Route::get('/childcategory/status/{id1}/{id2}', 'Admin\ChildCategoryController@status')->name('admin-childcat-status');
        Route::get('/load/childcategories/{id}/', 'Admin\ChildCategoryController@load')->name('admin-childcat-load'); //JSON REQUEST

        // CHILDCATEGORY SECTION ENDS------------

    });

    //------------ ADMIN MANAGE CATEGORY SECTION ENDS------------

    //------------ ADMIN CATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:catalog_items'], function () {
        Route::get('/catalog-items/datatables', 'Admin\CatalogItemController@datatables')->name('admin-catalog-item-datatables');
        Route::get('/catalog-items', 'Admin\CatalogItemController@index')->name('admin-catalog-item-index');
        Route::post('/catalog-items/upload/update/{id}', 'Admin\CatalogItemController@uploadUpdate')->name('admin-catalog-item-upload-update');
        Route::get('/catalog-items/deactive', 'Admin\CatalogItemController@deactive')->name('admin-catalog-item-deactive');
        Route::get('/catalog-items/catalogs/datatables', 'Admin\CatalogItemController@catalogdatatables')->name('admin-catalog-item-catalog-datatables');
        Route::get('/catalog-items/catalogs/', 'Admin\CatalogItemController@catalogItemsCatalog')->name('admin-catalog-item-catalog-index');

        // CREATE SECTION
        Route::get('/catalog-items/types', 'Admin\CatalogItemController@types')->name('admin-catalog-item-types');
        Route::get('/catalog-items/{slug}/create', 'Admin\CatalogItemController@create')->name('admin-catalog-item-create');
        Route::post('/catalog-items/store', 'Admin\CatalogItemController@store')->name('admin-catalog-item-store');
        Route::get('/getattributes', 'Admin\CatalogItemController@getAttributes')->name('admin-catalog-item-getattributes');
        Route::get('/get/crosscatalogitem/{catid}', 'Admin\CatalogItemController@getCrossCatalogItem');

        // EDIT SECTION
        Route::get('/catalog-items/edit/{merchantItemId}', 'Admin\CatalogItemController@edit')->name('admin-catalog-item-edit');
        Route::post('/catalog-items/edit/{merchantItemId}', 'Admin\CatalogItemController@update')->name('admin-catalog-item-update');

        // DELETE SECTION
        Route::delete('/catalog-items/delete/{id}', 'Admin\CatalogItemController@destroy')->name('admin-catalog-item-delete');

        Route::get('/catalog-items/catalog/{id1}/{id2}', 'Admin\CatalogItemController@catalog')->name('admin-catalog-item-catalog');
        Route::get('/catalog-items/feature/{id}', 'Admin\CatalogItemController@feature')->name('admin-catalog-item-feature');
        Route::post('/catalog-items/feature/{id}', 'Admin\CatalogItemController@featuresubmit')->name('admin-catalog-item-feature.store');
        Route::get('/catalog-items/status/{id1}/{id2}', 'Admin\CatalogItemController@status')->name('admin-catalog-item-status');
        Route::get('/merchant-items/status/{id}/{status}', 'Admin\CatalogItemController@merchantItemStatus')->name('admin-merchant-item-status');
        Route::get('/catalog-items/settings', 'Admin\CatalogItemController@catalogItemSettings')->name('admin-gs-catalog-item-settings');
        Route::post('/catalog-items/settings/update', 'Admin\CatalogItemController@settingUpdate')->name('admin-gs-catalog-item-settings-update');
    });

    //------------ ADMIN CATALOG ITEM SECTION ENDS------------

    //------------ ADMIN AFFILIATE CATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:affilate_catalog_items'], function () {
        Route::get('/catalog-items/import/create', 'Admin\ImportController@createImport')->name('admin-import-create');
        Route::get('/catalog-items/import/edit/{id}', 'Admin\ImportController@edit')->name('admin-import-edit');
        Route::get('/catalog-items/import/datatables', 'Admin\ImportController@datatables')->name('admin-import-datatables');
        Route::get('/catalog-items/import/index', 'Admin\ImportController@index')->name('admin-import-index');
        Route::post('/catalog-items/import/store', 'Admin\ImportController@store')->name('admin-import-store');
        Route::post('/catalog-items/import/update/{id}', 'Admin\ImportController@update')->name('admin-import-update');
        Route::delete('/affiliate/catalog-items/delete/{id}', 'Admin\CatalogItemController@destroy')->name('admin-affiliate-catalog-item-delete');
    });

    //------------ ADMIN AFFILIATE CATALOG ITEM SECTION ENDS ------------

    //------------ ADMIN CSV IMPORT SECTION ------------

    Route::group(['middleware' => 'permissions:bulk_catalog_item_upload'], function () {
        Route::get('/catalog-items/import', 'Admin\CatalogItemController@import')->name('admin-catalog-item-import');
        Route::post('/catalog-items/import-submit', 'Admin\CatalogItemController@importSubmit')->name('admin-catalog-item-importsubmit');
    });

    //------------ ADMIN CSV IMPORT SECTION ENDS ------------

    //------------ ADMIN PRODUCT DISCUSSION SECTION ------------

    Route::group(['middleware' => 'permissions:product_discussion'], function () {

        // CATALOG REVIEW SECTION ------------

        Route::get('/catalog-reviews/datatables', 'Admin\CatalogReviewController@datatables')->name('admin-catalog-review-datatables'); //JSON REQUEST
        Route::get('/catalog-reviews', 'Admin\CatalogReviewController@index')->name('admin-catalog-review-index');
        Route::delete('/catalog-reviews/delete/{id}', 'Admin\CatalogReviewController@destroy')->name('admin-catalog-review-delete');
        Route::get('/catalog-reviews/show/{id}', 'Admin\CatalogReviewController@show')->name('admin-catalog-review-show');

        // CATALOG REVIEW SECTION ENDS------------

        // COMMENT SECTION ------------

        Route::get('/comments/datatables', 'Admin\CommentController@datatables')->name('admin-comment-datatables'); //JSON REQUEST
        Route::get('/comments', 'Admin\CommentController@index')->name('admin-comment-index');
        Route::delete('/comments/delete/{id}', 'Admin\CommentController@destroy')->name('admin-comment-delete');
        Route::get('/comments/show/{id}', 'Admin\CommentController@show')->name('admin-comment-show');

        // COMMENT SECTION ENDS ------------

        // REPORT SECTION ------------

        Route::get('/reports/datatables', 'Admin\ReportController@datatables')->name('admin-report-datatables'); //JSON REQUEST
        Route::get('/reports', 'Admin\ReportController@index')->name('admin-report-index');
        Route::delete('/reports/delete/{id}', 'Admin\ReportController@destroy')->name('admin-report-delete');
        Route::get('/reports/show/{id}', 'Admin\ReportController@show')->name('admin-report-show');

        // REPORT SECTION ENDS ------------

    });

    //------------ ADMIN PRODUCT DISCUSSION SECTION ENDS ------------

    //------------ ADMIN DISCOUNT CODE SECTION ------------

    Route::group(['middleware' => 'permissions:set_discount_codes'], function () {

        Route::get('/discount-code/datatables', 'Admin\DiscountCodeController@datatables')->name('admin-discount-code-datatables'); //JSON REQUEST
        Route::get('/discount-code', 'Admin\DiscountCodeController@index')->name('admin-discount-code-index');
        Route::get('/discount-code/create', 'Admin\DiscountCodeController@create')->name('admin-discount-code-create');
        Route::post('/discount-code/create', 'Admin\DiscountCodeController@store')->name('admin-discount-code-store');
        Route::get('/discount-code/edit/{id}', 'Admin\DiscountCodeController@edit')->name('admin-discount-code-edit');
        Route::post('/discount-code/edit/{id}', 'Admin\DiscountCodeController@update')->name('admin-discount-code-update');
        Route::delete('/discount-code/delete/{id}', 'Admin\DiscountCodeController@destroy')->name('admin-discount-code-delete');
        Route::get('/discount-code/status/{id1}/{id2}', 'Admin\DiscountCodeController@status')->name('admin-discount-code-status');
    });

    //------------ ADMIN DISCOUNT CODE SECTION ENDS------------

    //------------ ADMIN USER SECTION ------------

    Route::group(['middleware' => 'permissions:customers'], function () {

        Route::get('/users/datatables', 'Admin\UserController@datatables')->name('admin-user-datatables'); //JSON REQUEST
        Route::get('/users', 'Admin\UserController@index')->name('admin-user-index');
        Route::get('/users/create', 'Admin\UserController@create')->name('admin-user-create');
        Route::post('/users/store', 'Admin\UserController@store')->name('admin-user-store');
        Route::get('/users/edit/{id}', 'Admin\UserController@edit')->name('admin-user-edit');
        Route::post('/users/edit/{id}', 'Admin\UserController@update')->name('admin-user-update');
        Route::delete('/users/delete/{id}', 'Admin\UserController@destroy')->name('admin-user-delete');
        Route::get('/user/{id}/show', 'Admin\UserController@show')->name('admin-user-show');
        Route::get('/users/ban/{id1}/{id2}', 'Admin\UserController@ban')->name('admin-user-ban');
        Route::get('/user/default/image', 'Admin\MuaadhSettingController@user_image')->name('admin-user-image');
        Route::get('/users/deposit/{id}', 'Admin\UserController@deposit')->name('admin-user-deposit');
        Route::post('/user/deposit/{id}', 'Admin\UserController@depositUpdate')->name('admin-user-deposit-update');
        Route::get('/users/vendor/{id}', 'Admin\UserController@vendor')->name('admin-user-vendor');
        Route::post('/user/vendor/{id}', 'Admin\UserController@setVendor')->name('admin-user-vendor-update');

        //USER WITHDRAW SECTION

        Route::get('/users/withdraws/datatables', 'Admin\UserController@withdrawdatatables')->name('admin-withdraw-datatables'); //JSON REQUEST
        Route::get('/users/withdraws', 'Admin\UserController@withdraws')->name('admin-withdraw-index');
        Route::get('/user/withdraw/{id}/show', 'Admin\UserController@withdrawdetails')->name('admin-withdraw-show');
        Route::get('/users/withdraws/accept/{id}', 'Admin\UserController@accept')->name('admin-withdraw-accept');
        Route::get('/user/withdraws/reject/{id}', 'Admin\UserController@reject')->name('admin-withdraw-reject');

        // WITHDRAW SECTION ENDS

        //RIDER WITHDRAW SECTION

        Route::get('/rider/withdraws/datatables', 'Admin\RiderController@withdrawdatatables')->name('admin-withdraw-rider-datatables'); //JSON REQUEST
        Route::get('/rider/withdraws', 'Admin\RiderController@withdraws')->name('admin-withdraw-rider-index');
        Route::get('/rider/withdraw/show/{id}', 'Admin\RiderController@withdrawdetails')->name('admin-withdraw-rider-show');
        Route::get('/rider/withdraw/accept/{id}', 'Admin\RiderController@accept')->name('admin-withdraw-rider-accept');
        Route::get('/rider/withdraw/reject/{id}', 'Admin\RiderController@reject')->name('admin-withdraw-rider-reject');

        // WITHDRAW SECTION ENDS

    });

    Route::group(['middleware' => 'permissions:riders'], function () {

        Route::get('/riders/datatables', 'Admin\RiderController@datatables')->name('admin-rider-datatables'); //JSON REQUEST
        Route::get('/riders', 'Admin\RiderController@index')->name('admin-rider-index');

        Route::delete('/riders/delete/{id}', 'Admin\RiderController@destroy')->name('admin-rider-delete');
        Route::get('/rider/{id}/show', 'Admin\RiderController@show')->name('admin-rider-show');
        Route::get('/riders/ban/{id1}/{id2}', 'Admin\RiderController@ban')->name('admin-rider-ban');
        Route::get('/rider/default/image', 'Admin\MuaadhSettingController@rider_image')->name('admin-rider-image');

        // WITHDRAW SECTION

        Route::get('/riders/withdraws/datatables', 'Admin\RiderController@withdrawdatatables')->name('admin-rider-withdraw-datatables'); //JSON REQUEST
        Route::get('/riders/withdraws', 'Admin\RiderController@withdraws')->name('admin-rider-withdraw-index');
        Route::get('/rider/withdraw/{id}/show', 'Admin\RiderController@withdrawdetails')->name('admin-rider-withdraw-show');
        Route::get('/riders/withdraws/accept/{id}', 'Admin\RiderController@accept')->name('admin-rider-withdraw-accept');
        Route::get('/rider/withdraws/reject/{id}', 'Admin\RiderController@reject')->name('admin-rider-withdraw-reject');

        // WITHDRAW SECTION ENDS

    });

    //------------ ADMIN USER DEPOSIT & TRANSACTION SECTION ------------

    Route::group(['middleware' => 'permissions:customer_deposits'], function () {

        Route::get('/users/deposit/datatables/{status}', 'Admin\UserDepositController@datatables')->name('admin-user-deposit-datatables'); //JSON REQUEST
        Route::get('/users/deposits/{slug}', 'Admin\UserDepositController@deposits')->name('admin-user-deposits');
        Route::get('/users/deposits/status/{id1}/{id2}', 'Admin\UserDepositController@status')->name('admin-user-deposit-status');
        Route::get('/users/transactions/datatables', 'Admin\UserTransactionController@transdatatables')->name('admin-trans-datatables'); //JSON REQUEST
        Route::get('/users/transactions', 'Admin\UserTransactionController@index')->name('admin-trans-index');
        Route::get('/users/transactions/{id}/show', 'Admin\UserTransactionController@transhow')->name('admin-trans-show');
    });

    //------------ ADMIN USER DEPOSIT & TRANSACTION SECTION ------------

    //------------ ADMIN MERCHANT SECTION ------------

    Route::group(['middleware' => 'permissions:vendors'], function () {

        Route::get('/merchants/datatables', 'Admin\MerchantController@datatables')->name('admin-merchant-datatables');
        Route::get('/merchants', 'Admin\MerchantController@index')->name('admin-merchant-index');

        Route::get('/merchants/{id}/show', 'Admin\MerchantController@show')->name('admin-merchant-show');
        Route::get('/merchants/secret/login/{id}', 'Admin\MerchantController@secret')->name('admin-merchant-secret');
        Route::get('/merchant/edit/{id}', 'Admin\MerchantController@edit')->name('admin-merchant-edit');
        Route::post('/merchant/edit/{id}', 'Admin\MerchantController@update')->name('admin-merchant-update');

        Route::get('/merchant/verify/{id}', 'Admin\MerchantController@verify')->name('admin-merchant-verify');
        Route::post('/merchant/verify/{id}', 'Admin\MerchantController@verifySubmit')->name('admin-merchant-verify-submit');

        Route::get('/add/subscription/{id}', 'Admin\MerchantController@addSubs')->name('admin-merchant-add-subs');
        Route::post('/add/subscription/{id}', 'Admin\MerchantController@addSubsStore')->name('admin-merchant-subs-store');

        Route::get('/merchant/color', 'Admin\MuaadhSettingController@merchant_color')->name('admin-merchant-color');
        Route::get('/merchants/status/{id1}/{id2}', 'Admin\MerchantController@status')->name('admin-merchant-st');
        Route::delete('/merchants/delete/{id}', 'Admin\MerchantController@destroy')->name('admin-merchant-delete');
        Route::get('/merchant/commission/collect/{id}', 'Admin\MerchantController@commissionCollect')->name('admin-merchant-commission-collect');

        Route::get('/merchants/withdraws/datatables', 'Admin\MerchantController@withdrawdatatables')->name('admin-merchant-withdraw-datatables'); //JSON REQUEST
        Route::get('/merchants/withdraws', 'Admin\MerchantController@withdraws')->name('admin-merchant-withdraw-index');
        Route::get('/merchants/withdraw/{id}/show', 'Admin\MerchantController@withdrawdetails')->name('admin-merchant-withdraw-show');
        Route::get('/merchants/withdraws/accept/{id}', 'Admin\MerchantController@accept')->name('admin-merchant-withdraw-accept');
        Route::get('/merchants/withdraws/reject/{id}', 'Admin\MerchantController@reject')->name('admin-merchant-withdraw-reject');
    });

    //------------ ADMIN MERCHANT SECTION ENDS ------------

    //------------ ADMIN SUBSCRIPTION SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_subscriptions'], function () {

        Route::get('/subscription/datatables', 'Admin\SubscriptionController@datatables')->name('admin-subscription-datatables');
        Route::get('/subscription', 'Admin\SubscriptionController@index')->name('admin-subscription-index');
        Route::get('/subscription/create', 'Admin\SubscriptionController@create')->name('admin-subscription-create');
        Route::post('/subscription/create', 'Admin\SubscriptionController@store')->name('admin-subscription-store');
        Route::get('/subscription/edit/{id}', 'Admin\SubscriptionController@edit')->name('admin-subscription-edit');
        Route::post('/subscription/edit/{id}', 'Admin\SubscriptionController@update')->name('admin-subscription-update');
        Route::delete('/subscription/delete/{id}', 'Admin\SubscriptionController@destroy')->name('admin-subscription-delete');
    });

    //------------ ADMIN SUBSCRIPTION SECTION ENDS ------------

    //------------ ADMIN VENDOR VERIFICATION SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_verifications'], function () {

        Route::get('/verificatons/datatables/{status}', 'Admin\VerificationController@datatables')->name('admin-vr-datatables');
        Route::get('/verificatons/{slug}', 'Admin\VerificationController@verificatons')->name('admin-vr-index');
        Route::get('/verificatons/show/attachment', 'Admin\VerificationController@show')->name('admin-vr-show');
        Route::get('/verificatons/edit/{id}', 'Admin\VerificationController@edit')->name('admin-vr-edit');
        Route::post('/verificatons/edit/{id}', 'Admin\VerificationController@update')->name('admin-vr-update');
        Route::get('/verificatons/status/{id1}/{id2}', 'Admin\VerificationController@status')->name('admin-vr-st');
        Route::delete('/verificatons/delete/{id}', 'Admin\VerificationController@destroy')->name('admin-vr-delete');
    });

    //------------ ADMIN VENDOR VERIFICATION SECTION ENDS ------------

    //------------ ADMIN MERCHANT SUBSCRIPTION PLAN SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_subscription_plans'], function () {

        Route::get('/merchants/subs/datatables/{status}', 'Admin\MerchantSubscriptionController@subsdatatables')->name('admin-merchant-subs-datatables');
        Route::get('/merchants/subs/{slug}', 'Admin\MerchantSubscriptionController@subs')->name('admin-merchant-subs');
        Route::get('/merchants/subs/status/{id1}/{id2}', 'Admin\MerchantSubscriptionController@status')->name('admin-user-sub-status');
        Route::get('/merchants/sub/{id}', 'Admin\MerchantSubscriptionController@sub')->name('admin-merchant-sub');
    });

    //------------ ADMIN MERCHANT SUBSCRIPTION PLAN SECTION ------------

    //------------ ADMIN USER MESSAGE SECTION ------------

    Route::group(['middleware' => 'permissions:messages'], function () {

        Route::get('/messages/datatables/{type}', 'Admin\MessageController@datatables')->name('admin-message-datatables');
        Route::get('/tickets', 'Admin\MessageController@index')->name('admin-message-index');
        Route::get('/disputes', 'Admin\MessageController@dispute')->name('admin-message-dispute');
        Route::get('/message/{id}', 'Admin\MessageController@message')->name('admin-message-show');
        Route::get('/message/load/{id}', 'Admin\MessageController@messageshow')->name('admin-message-load');
        Route::post('/message/post', 'Admin\MessageController@postmessage')->name('admin-message-store');
        Route::delete('/message/{id}/delete', 'Admin\MessageController@messagedelete')->name('admin-message-delete');
        Route::post('/user/send/message/admin', 'Admin\MessageController@usercontact')->name('admin-send-message');
    });

    //------------ ADMIN USER MESSAGE SECTION ENDS ------------

    //------------ ADMIN BLOG SECTION ------------

    Route::group(['middleware' => 'permissions:blog'], function () {

        Route::get('/blog/datatables', 'Admin\BlogController@datatables')->name('admin-blog-datatables'); //JSON REQUEST
        Route::get('/blog', 'Admin\BlogController@index')->name('admin-blog-index');
        Route::get('/blog/create', 'Admin\BlogController@create')->name('admin-blog-create');
        Route::post('/blog/create', 'Admin\BlogController@store')->name('admin-blog-store');
        Route::get('/blog/edit/{id}', 'Admin\BlogController@edit')->name('admin-blog-edit');
        Route::post('/blog/edit/{id}', 'Admin\BlogController@update')->name('admin-blog-update');
        Route::delete('/blog/delete/{id}', 'Admin\BlogController@destroy')->name('admin-blog-delete');

        Route::get('/blog/category/datatables', 'Admin\BlogCategoryController@datatables')->name('admin-cblog-datatables'); //JSON REQUEST
        Route::get('/blog/category', 'Admin\BlogCategoryController@index')->name('admin-cblog-index');
        Route::get('/blog/category/create', 'Admin\BlogCategoryController@create')->name('admin-cblog-create');
        Route::post('/blog/category/create', 'Admin\BlogCategoryController@store')->name('admin-cblog-store');
        Route::get('/blog/category/edit/{id}', 'Admin\BlogCategoryController@edit')->name('admin-cblog-edit');
        Route::post('/blog/category/edit/{id}', 'Admin\BlogCategoryController@update')->name('admin-cblog-update');
        Route::delete('/blog/category/delete/{id}', 'Admin\BlogCategoryController@destroy')->name('admin-cblog-delete');

        Route::get('/blog/blog-settings', 'Admin\BlogController@settings')->name('admin-gs-blog-settings');
    });

    //------------ ADMIN BLOG SECTION ENDS ------------

    //------------ ADMIN GENERAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:muaadh_settings'], function () {

        Route::get('/general-settings/logo', 'Admin\MuaadhSettingController@logo')->name('admin-gs-logo');
        Route::get('/general-settings/favicon', 'Admin\MuaadhSettingController@favicon')->name('admin-gs-fav');
        Route::get('/general-settings/loader', 'Admin\MuaadhSettingController@loader')->name('admin-gs-load');
        Route::get('/general-settings/contents', 'Admin\MuaadhSettingController@websitecontent')->name('admin-gs-contents');
        Route::get('/general-settings/theme-colors', 'Admin\MuaadhSettingController@themeColors')->name('admin-theme-colors');
        Route::post('/general-settings/theme-colors/update', 'Admin\MuaadhSettingController@updateThemeColors')->name('admin-theme-colors-update');
        Route::get('/general-settings/affilate', 'Admin\MuaadhSettingController@affilate')->name('admin-gs-affilate');
        Route::get('/general-settings/error-banner', 'Admin\MuaadhSettingController@error_banner')->name('admin-gs-error-banner');
        Route::get('/general-settings/popup', 'Admin\MuaadhSettingController@popup')->name('admin-gs-popup');
        // Breadcrumb banner removed - using modern minimal design
        Route::get('/general-settings/maintenance', 'Admin\MuaadhSettingController@maintain')->name('admin-gs-maintenance');

        // Deal Of The Day

        //------------ ADMIN PICKUP LOACTION ------------

        Route::get('/pickup/datatables', 'Admin\PickupController@datatables')->name('admin-pick-datatables'); //JSON REQUEST
        Route::get('/pickup', 'Admin\PickupController@index')->name('admin-pick-index');
        Route::get('/pickup/create', 'Admin\PickupController@create')->name('admin-pick-create');
        Route::post('/pickup/create', 'Admin\PickupController@store')->name('admin-pick-store');
        Route::get('/pickup/edit/{id}', 'Admin\PickupController@edit')->name('admin-pick-edit');
        Route::post('/pickup/edit/{id}', 'Admin\PickupController@update')->name('admin-pick-update');
        Route::delete('/pickup/delete/{id}', 'Admin\PickupController@destroy')->name('admin-pick-delete');

        //------------ ADMIN PICKUP LOACTION ENDS ------------

        //------------ ADMIN SHIPPING ------------

        Route::get('/shipping/datatables', 'Admin\ShippingController@datatables')->name('admin-shipping-datatables');
        Route::get('/shipping', 'Admin\ShippingController@index')->name('admin-shipping-index');
        Route::get('/shipping/create', 'Admin\ShippingController@create')->name('admin-shipping-create');
        Route::post('/shipping/create', 'Admin\ShippingController@store')->name('admin-shipping-store');
        Route::get('/shipping/edit/{id}', 'Admin\ShippingController@edit')->name('admin-shipping-edit');
        Route::post('/shipping/edit/{id}', 'Admin\ShippingController@update')->name('admin-shipping-update');
        Route::delete('/shipping/delete/{id}', 'Admin\ShippingController@destroy')->name('admin-shipping-delete');

        //------------ ADMIN SHIPPING ENDS ------------

        //------------ ADMIN PACKAGE ------------

        Route::get('/package/datatables', 'Admin\PackageController@datatables')->name('admin-package-datatables');
        Route::get('/package', 'Admin\PackageController@index')->name('admin-package-index');
        Route::get('/package/create', 'Admin\PackageController@create')->name('admin-package-create');
        Route::post('/package/create', 'Admin\PackageController@store')->name('admin-package-store');
        Route::get('/package/edit/{id}', 'Admin\PackageController@edit')->name('admin-package-edit');
        Route::post('/package/edit/{id}', 'Admin\PackageController@update')->name('admin-package-update');
        Route::delete('/package/delete/{id}', 'Admin\PackageController@destroy')->name('admin-package-delete');

        //------------ ADMIN PACKAGE ENDS------------

    });

    //------------ ADMIN GENERAL SETTINGS SECTION ENDS ------------

    //------------ ADMIN HOME PAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:home_page_settings'], function () {

        Route::get('/home-page-settings', 'Admin\MuaadhSettingController@homepage')->name('admin-home-page-index');

        //------------ ADMIN SLIDER SECTION ------------

        Route::get('/slider/datatables', 'Admin\SliderController@datatables')->name('admin-sl-datatables'); //JSON REQUEST
        Route::get('/slider', 'Admin\SliderController@index')->name('admin-sl-index');
        Route::get('/slider/create', 'Admin\SliderController@create')->name('admin-sl-create');
        Route::post('/slider/create', 'Admin\SliderController@store')->name('admin-sl-store');
        Route::get('/slider/edit/{id}', 'Admin\SliderController@edit')->name('admin-sl-edit');
        Route::post('/slider/edit/{id}', 'Admin\SliderController@update')->name('admin-sl-update');
        Route::delete('/slider/delete/{id}', 'Admin\SliderController@destroy')->name('admin-sl-delete');

        //------------ ADMIN SLIDER SECTION ENDS ------------

        //------------ ADMIN HOME PAGE THEMES SECTION ------------

        Route::get('/home-themes', 'Admin\HomePageThemeController@index')->name('admin-homethemes-index');
        Route::get('/home-themes/create', 'Admin\HomePageThemeController@create')->name('admin-homethemes-create');
        Route::post('/home-themes/store', 'Admin\HomePageThemeController@store')->name('admin-homethemes-store');
        Route::get('/home-themes/edit/{id}', 'Admin\HomePageThemeController@edit')->name('admin-homethemes-edit');
        Route::put('/home-themes/update/{id}', 'Admin\HomePageThemeController@update')->name('admin-homethemes-update');
        Route::get('/home-themes/activate/{id}', 'Admin\HomePageThemeController@activate')->name('admin-homethemes-activate');
        Route::get('/home-themes/duplicate/{id}', 'Admin\HomePageThemeController@duplicate')->name('admin-homethemes-duplicate');
        Route::delete('/home-themes/delete/{id}', 'Admin\HomePageThemeController@destroy')->name('admin-homethemes-delete');

        //------------ ADMIN HOME PAGE THEMES SECTION ENDS ------------

        Route::get('/arrival/datatables', 'Admin\ArrivalsectionController@datatables')->name('admin-arrival-datatables');
        Route::get('/arrival', 'Admin\ArrivalsectionController@index')->name('admin-arrival-index');
        Route::get('/arrival/create', 'Admin\ArrivalsectionController@create')->name('admin-arrival-create');
        Route::post('/arrival/create', 'Admin\ArrivalsectionController@store')->name('admin-arrival-store');
        Route::get('/arrival/edit/{id}', 'Admin\ArrivalsectionController@edit')->name('admin-arrival-edit');
        Route::post('/arrival/edit/{id}', 'Admin\ArrivalsectionController@update')->name('admin-arrival-update');
        Route::delete('/arrival/delete/{id}', 'Admin\ArrivalsectionController@destroy')->name('admin-arrival-delete');
        Route::get('/country/status/{id1}/{id2}', 'Admin\ArrivalsectionController@status')->name('admin-arrival-status');

        //------------ ADMIN SERVICE SECTION ------------

        Route::get('/service/datatables', 'Admin\ServiceController@datatables')->name('admin-service-datatables'); //JSON REQUEST
        Route::get('/service', 'Admin\ServiceController@index')->name('admin-service-index');
        Route::get('/service/create', 'Admin\ServiceController@create')->name('admin-service-create');
        Route::post('/service/create', 'Admin\ServiceController@store')->name('admin-service-store');
        Route::get('/service/edit/{id}', 'Admin\ServiceController@edit')->name('admin-service-edit');
        Route::post('/service/edit/{id}', 'Admin\ServiceController@update')->name('admin-service-update');
        Route::delete('/service/delete/{id}', 'Admin\ServiceController@destroy')->name('admin-service-delete');

        //------------ ADMIN SERVICE SECTION ENDS ------------

        //------------ ADMIN BANNER SECTION ------------

        Route::get('/banner/datatables/{type}', 'Admin\BannerController@datatables')->name('admin-sb-datatables'); //JSON REQUEST
        Route::get('large/banner/', 'Admin\BannerController@large')->name('admin-sb-large');
        Route::get('large/banner/create', 'Admin\BannerController@largecreate')->name('admin-sb-create-large');
        Route::post('/banner/create', 'Admin\BannerController@store')->name('admin-sb-store');
        Route::get('/banner/edit/{id}', 'Admin\BannerController@edit')->name('admin-sb-edit');
        Route::post('/banner/edit/{id}', 'Admin\BannerController@update')->name('admin-sb-update');
        Route::delete('/banner/delete/{id}', 'Admin\BannerController@destroy')->name('admin-sb-delete');

        //------------ ADMIN BANNER SECTION ENDS ------------

        //------------ ADMIN BRAND SECTION ------------

        Route::get('/brand/datatables', 'Admin\BrandController@datatables')->name('admin-brand-datatables');
        Route::get('/brand', 'Admin\BrandController@index')->name('admin-brand-index');
        Route::get('/brand/create', 'Admin\BrandController@create')->name('admin-brand-create');
        Route::post('/brand/create', 'Admin\BrandController@store')->name('admin-brand-store');
        Route::get('/brand/edit/{id}', 'Admin\BrandController@edit')->name('admin-brand-edit');
        Route::post('/brand/edit/{id}', 'Admin\BrandController@update')->name('admin-brand-update');
        Route::delete('/brand/delete/{id}', 'Admin\BrandController@destroy')->name('admin-brand-delete');

        //------------ ADMIN BRAND SECTION ENDS ------------

        //------------ ADMIN PAGE SETTINGS SECTION ------------

        Route::get('/page-settings/customize', 'Admin\PageSettingController@customize')->name('admin-ps-customize');
        Route::get('/page-settings/best-seller', 'Admin\PageSettingController@best_seller')->name('admin-ps-best-seller');
    });

    //------------ ADMIN HOME PAGE SETTINGS SECTION ENDS ------------

    Route::group(['middleware' => 'permissions:menu_page_settings'], function () {

        //------------ ADMIN MENU PAGE SETTINGS SECTION ------------

        //------------ ADMIN FAQ SECTION ------------

        Route::get('/faq/datatables', 'Admin\FaqController@datatables')->name('admin-faq-datatables'); //JSON REQUEST
        Route::get('/faq', 'Admin\FaqController@index')->name('admin-faq-index');
        Route::get('/faq/create', 'Admin\FaqController@create')->name('admin-faq-create');
        Route::post('/faq/create', 'Admin\FaqController@store')->name('admin-faq-store');
        Route::get('/faq/edit/{id}', 'Admin\FaqController@edit')->name('admin-faq-edit');
        Route::post('/faq/update/{id}', 'Admin\FaqController@update')->name('admin-faq-update');
        Route::delete('/faq/delete/{id}', 'Admin\FaqController@destroy')->name('admin-faq-delete');

        //------------ ADMIN FAQ SECTION ENDS ------------

        //------------ ADMIN PAGE SECTION ------------

        Route::get('/page/datatables', 'Admin\PageController@datatables')->name('admin-page-datatables'); //JSON REQUEST
        Route::get('/page', 'Admin\PageController@index')->name('admin-page-index');
        Route::get('/page/create', 'Admin\PageController@create')->name('admin-page-create');
        Route::post('/page/create', 'Admin\PageController@store')->name('admin-page-store');
        Route::get('/page/edit/{id}', 'Admin\PageController@edit')->name('admin-page-edit');
        Route::post('/page/update/{id}', 'Admin\PageController@update')->name('admin-page-update');
        Route::delete('/page/delete/{id}', 'Admin\PageController@destroy')->name('admin-page-delete');
        Route::get('/page/header/{id1}/{id2}', 'Admin\PageController@header')->name('admin-page-header');
        Route::get('/page/footer/{id1}/{id2}', 'Admin\PageController@footer')->name('admin-page-footer');
        Route::get('/page/banner', 'Admin\PageSettingController@page_banner')->name('admin-ps-page-banner');
        Route::get('/right/banner', 'Admin\PageSettingController@right_banner')->name('admin-ps-right-banner');
        Route::get('/menu/links', 'Admin\PageSettingController@menu_links')->name('admin-ps-menu-links');
        Route::get('/deal/of/day', 'Admin\PageSettingController@deal')->name('admin-ps-deal');
        Route::post('/deal/of/day/toggle', 'Admin\PageSettingController@toggleDeal')->name('admin-ps-deal-toggle');
        Route::get('/deal/of/day/search', 'Admin\PageSettingController@searchDealProducts')->name('admin-ps-deal-search');
        Route::get('/deal/of/day/merchants', 'Admin\PageSettingController@getProductMerchants')->name('admin-ps-deal-merchants');

        // Best Sellers Management
        Route::get('/best-sellers', 'Admin\PageSettingController@bestSellers')->name('admin-ps-best-sellers');
        Route::post('/best-sellers/toggle', 'Admin\PageSettingController@toggleBestSellers')->name('admin-ps-best-sellers-toggle');
        Route::get('/best-sellers/search', 'Admin\PageSettingController@searchBestSellersProducts')->name('admin-ps-best-sellers-search');
        Route::get('/best-sellers/merchants', 'Admin\PageSettingController@getBestSellersMerchants')->name('admin-ps-best-sellers-merchants');

        // Top Rated Management
        Route::get('/top-rated', 'Admin\PageSettingController@topRated')->name('admin-ps-top-rated');
        Route::post('/top-rated/toggle', 'Admin\PageSettingController@toggleTopRated')->name('admin-ps-top-rated-toggle');
        Route::get('/top-rated/search', 'Admin\PageSettingController@searchTopRated')->name('admin-ps-top-rated-search');
        Route::get('/top-rated/merchants', 'Admin\PageSettingController@getTopRatedMerchants')->name('admin-ps-top-rated-merchants');

        // Big Save Management
        Route::get('/big-save', 'Admin\PageSettingController@bigSave')->name('admin-ps-big-save');
        Route::post('/big-save/toggle', 'Admin\PageSettingController@toggleBigSave')->name('admin-ps-big-save-toggle');
        Route::get('/big-save/search', 'Admin\PageSettingController@searchBigSave')->name('admin-ps-big-save-search');
        Route::get('/big-save/merchants', 'Admin\PageSettingController@getBigSaveMerchants')->name('admin-ps-big-save-merchants');

        // Trending Management
        Route::get('/trending', 'Admin\PageSettingController@trending')->name('admin-ps-trending');
        Route::post('/trending/toggle', 'Admin\PageSettingController@toggleTrending')->name('admin-ps-trending-toggle');
        Route::get('/trending/search', 'Admin\PageSettingController@searchTrending')->name('admin-ps-trending-search');
        Route::get('/trending/merchants', 'Admin\PageSettingController@getTrendingMerchants')->name('admin-ps-trending-merchants');

        // Featured Products Management
        Route::get('/featured', 'Admin\PageSettingController@featured')->name('admin-ps-featured');
        Route::post('/featured/toggle', 'Admin\PageSettingController@toggleFeatured')->name('admin-ps-featured-toggle');
        Route::get('/featured/search', 'Admin\PageSettingController@searchFeatured')->name('admin-ps-featured-search');
        Route::get('/featured/merchants', 'Admin\PageSettingController@getFeaturedMerchants')->name('admin-ps-featured-merchants');
        //------------ ADMIN PAGE SECTION ENDS------------

        Route::get('/page-settings/contact', 'Admin\PageSettingController@contact')->name('admin-ps-contact');
        Route::post('/page-settings/update/all', 'Admin\PageSettingController@update')->name('admin-ps-update');
    });

    //------------ ADMIN MENU PAGE SETTINGS SECTION ENDS ------------

    //------------ ADMIN EMAIL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:email_settings'], function () {

        Route::get('/email-templates/datatables', 'Admin\EmailController@datatables')->name('admin-mail-datatables');
        Route::get('/email-templates', 'Admin\EmailController@index')->name('admin-mail-index');
        Route::get('/email-templates/{id}', 'Admin\EmailController@edit')->name('admin-mail-edit');
        Route::post('/email-templates/{id}', 'Admin\EmailController@update')->name('admin-mail-update');
        Route::get('/email-config', 'Admin\EmailController@config')->name('admin-mail-config');
        Route::get('/groupemail', 'Admin\EmailController@groupemail')->name('admin-group-show');
        Route::post('/groupemailpost', 'Admin\EmailController@groupemailpost')->name('admin-group-submit');
    });

    if(addon("otp")){
        
    Route::group(['middleware' => 'permissions:otp_setting'], function () {
        Route::get('/opt/config', 'Admin\MuaadhSettingController@otpConfig')->name('admin-otp-config');
    });

    }

    //------------ ADMIN EMAIL SETTINGS SECTION ENDS ------------

    //------------ ADMIN PAYMENT SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:payment_settings'], function () {

        // Payment Informations

        Route::get('/payment-informations', 'Admin\MuaadhSettingController@paymentsinfo')->name('admin-gs-payments');

        // Payment Gateways

        Route::get('/paymentgateway/datatables', 'Admin\PaymentGatewayController@datatables')->name('admin-payment-datatables'); //JSON REQUEST
        Route::get('/paymentgateway', 'Admin\PaymentGatewayController@index')->name('admin-payment-index');
        Route::get('/paymentgateway/create', 'Admin\PaymentGatewayController@create')->name('admin-payment-create');
        Route::post('/paymentgateway/create', 'Admin\PaymentGatewayController@store')->name('admin-payment-store');
        Route::get('/paymentgateway/edit/{id}', 'Admin\PaymentGatewayController@edit')->name('admin-payment-edit');
        Route::post('/paymentgateway/update/{id}', 'Admin\PaymentGatewayController@update')->name('admin-payment-update');
        Route::delete('/paymentgateway/delete/{id}', 'Admin\PaymentGatewayController@destroy')->name('admin-payment-delete');
        Route::get('/paymentgateway/status/{field}/{id1}/{id2}', 'Admin\PaymentGatewayController@status')->name('admin-payment-status');

        // Currency Settings

        // MULTIPLE CURRENCY

        Route::get('/currency/datatables', 'Admin\CurrencyController@datatables')->name('admin-currency-datatables'); //JSON REQUEST
        Route::get('/currency', 'Admin\CurrencyController@index')->name('admin-currency-index');
        Route::get('/currency/create', 'Admin\CurrencyController@create')->name('admin-currency-create');
        Route::post('/currency/create', 'Admin\CurrencyController@store')->name('admin-currency-store');
        Route::get('/currency/edit/{id}', 'Admin\CurrencyController@edit')->name('admin-currency-edit');
        Route::post('/currency/update/{id}', 'Admin\CurrencyController@update')->name('admin-currency-update');
        Route::delete('/currency/delete/{id}', 'Admin\CurrencyController@destroy')->name('admin-currency-delete');
        Route::get('/currency/status/{id1}/{id2}', 'Admin\CurrencyController@status')->name('admin-currency-status');

        // -------------------- Reward Section Route ---------------------//
        Route::get('rewards/datatables', 'Admin\RewardController@datatables')->name('admin-reward-datatables');
        Route::get('rewards', 'Admin\RewardController@index')->name('admin-reward-index');
        Route::get('/general-settings/reward/{status}', 'Admin\MuaadhSettingController@isreward')->name('admin-gs-is_reward');
        Route::post('reward/update/', 'Admin\RewardController@update')->name('admin-reward-update');
        Route::post('reward/information/update', 'Admin\RewardController@infoUpdate')->name('admin-reward-info-update');

        // -------------------- Reward Section Route ---------------------//

    });

    //------------ ADMIN PAYMENT SETTINGS SECTION ENDS------------

    //------------ ADMIN SOCIAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:social_settings'], function () {

        //------------ ADMIN SOCIAL LINK ------------

        Route::get('/social-link/datatables', 'Admin\SocialLinkController@datatables')->name('admin-sociallink-datatables'); //JSON REQUEST
        Route::get('/social-link', 'Admin\SocialLinkController@index')->name('admin-sociallink-index');
        Route::get('/social-link/create', 'Admin\SocialLinkController@create')->name('admin-sociallink-create');
        Route::post('/social-link/create', 'Admin\SocialLinkController@store')->name('admin-sociallink-store');
        Route::get('/social-link/edit/{id}', 'Admin\SocialLinkController@edit')->name('admin-sociallink-edit');
        Route::post('/social-link/edit/{id}', 'Admin\SocialLinkController@update')->name('admin-sociallink-update');
        Route::delete('/social-link/delete/{id}', 'Admin\SocialLinkController@destroy')->name('admin-sociallink-delete');
        Route::get('/social-link/status/{id1}/{id2}', 'Admin\SocialLinkController@status')->name('admin-sociallink-status');

        //------------ ADMIN SOCIAL LINK ENDS ------------
        Route::get('/social', 'Admin\SocialSettingController@index')->name('admin-social-index');
        Route::post('/social/update', 'Admin\SocialSettingController@socialupdate')->name('admin-social-update');
        Route::post('/social/update/all', 'Admin\SocialSettingController@socialupdateall')->name('admin-social-update-all');
        Route::get('/social/facebook', 'Admin\SocialSettingController@facebook')->name('admin-social-facebook');
        Route::get('/social/google', 'Admin\SocialSettingController@google')->name('admin-social-google');
        Route::get('/social/facebook/{status}', 'Admin\SocialSettingController@facebookup')->name('admin-social-facebookup');
        Route::get('/social/google/{status}', 'Admin\SocialSettingController@googleup')->name('admin-social-googleup');
    });
    //------------ ADMIN SOCIAL SETTINGS SECTION ENDS------------

    //------------ ADMIN LANGUAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:language_settings'], function () {

        //  Multiple Language Section

        //  Multiple Language Section Ends

        Route::get('/languages/datatables', 'Admin\LanguageController@datatables')->name('admin-lang-datatables'); //JSON REQUEST
        Route::get('/languages', 'Admin\LanguageController@index')->name('admin-lang-index');
        Route::get('/languages/create', 'Admin\LanguageController@create')->name('admin-lang-create');
        Route::get('/languages/import', 'Admin\LanguageController@import')->name('admin-lang-import');
        Route::get('/languages/edit/{id}', 'Admin\LanguageController@edit')->name('admin-lang-edit');
        Route::get('/languages/export/{id}', 'Admin\LanguageController@export')->name('admin-lang-export');
        Route::post('/languages/create', 'Admin\LanguageController@store')->name('admin-lang-store');
        Route::post('/languages/import/create', 'Admin\LanguageController@importStore')->name('admin-lang-import-store');
        Route::post('/languages/edit/{id}', 'Admin\LanguageController@update')->name('admin-lang-update');
        Route::get('/languages/status/{id1}/{id2}', 'Admin\LanguageController@status')->name('admin-lang-st');
        Route::delete('/languages/delete/{id}', 'Admin\LanguageController@destroy')->name('admin-lang-delete');


        //------------ ADMIN LANGUAGE SETTINGS SECTION ENDS ------------

    });

    //------------ADMIN FONT SECTION------------------
    Route::get('/fonts/datatables', 'Admin\FontController@datatables')->name('admin.fonts.datatables');
    Route::get('/fonts', 'Admin\FontController@index')->name('admin.fonts.index');
    Route::get('/fonts/create', 'Admin\FontController@create')->name('admin.fonts.create');
    Route::post('/fonts/create', 'Admin\FontController@store')->name('admin.fonts.store');
    Route::get('/fonts/edit/{id}', 'Admin\FontController@edit')->name('admin.fonts.edit');
    Route::post('/fonts/edit/{id}', 'Admin\FontController@update')->name('admin.fonts.update');
    Route::delete('/fonts/delete/{id}', 'Admin\FontController@destroy')->name('admin.fonts.delete');
    Route::get('/fonts/status/{id}', 'Admin\FontController@status')->name('admin.fonts.status');
    //------------ADMIN FONT SECTION------------------

    //------------ ADMIN SEOTOOL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:seo_tools'], function () {

        Route::get('/seotools/analytics', 'Admin\SeoToolController@analytics')->name('admin-seotool-analytics');
        Route::post('/seotools/analytics/update', 'Admin\SeoToolController@analyticsupdate')->name('admin-seotool-analytics-update');
        Route::get('/seotools/keywords', 'Admin\SeoToolController@keywords')->name('admin-seotool-keywords');
        Route::post('/seotools/keywords/update', 'Admin\SeoToolController@keywordsupdate')->name('admin-seotool-keywords-update');
        Route::get('/catalog-items/popular/{id}', 'Admin\SeoToolController@popular')->name('admin-catalog-item-popular');
    });

    //------------ ADMIN SEOTOOL SETTINGS SECTION ------------

    //------------ ADMIN STAFF SECTION ------------

    Route::group(['middleware' => 'permissions:manage_staffs'], function () {

        Route::get('/staff/datatables', 'Admin\StaffController@datatables')->name('admin-staff-datatables');
        Route::get('/staff', 'Admin\StaffController@index')->name('admin-staff-index');
        Route::get('/staff/create', 'Admin\StaffController@create')->name('admin-staff-create');
        Route::post('/staff/create', 'Admin\StaffController@store')->name('admin-staff-store');
        Route::get('/staff/edit/{id}', 'Admin\StaffController@edit')->name('admin-staff-edit');
        Route::post('/staff/update/{id}', 'Admin\StaffController@update')->name('admin-staff-update');
        Route::get('/staff/show/{id}', 'Admin\StaffController@show')->name('admin-staff-show');
        Route::delete('/staff/delete/{id}', 'Admin\StaffController@destroy')->name('admin-staff-delete');
    });

    //------------ ADMIN STAFF SECTION ENDS------------

    //------------ ADMIN SUBSCRIBERS SECTION ------------

    Route::group(['middleware' => 'permissions:subscribers'], function () {

        Route::get('/subscribers/datatables', 'Admin\SubscriberController@datatables')->name('admin-subs-datatables'); //JSON REQUEST
        Route::get('/subscribers', 'Admin\SubscriberController@index')->name('admin-subs-index');
        Route::get('/subscribers/download', 'Admin\SubscriberController@download')->name('admin-subs-download');
    });

    //------------ ADMIN SUBSCRIBERS ENDS ------------

    // ------------ GLOBAL ----------------------
    Route::post('/general-settings/update/all', 'Admin\MuaadhSettingController@generalupdate')->name('admin-gs-update');
    Route::post('/general-settings/update/te=heme', 'Admin\MuaadhSettingController@updateTheme')->name('admin-gs-update-theme');
    Route::post('/general-settings/update/payment', 'Admin\MuaadhSettingController@generalupdatepayment')->name('admin-gs-update-payment');
    Route::post('/general-settings/update/mail', 'Admin\MuaadhSettingController@generalMailUpdate')->name('admin-gs-update-mail');
    Route::get('/general-settings/status/{field}/{status}', 'Admin\MuaadhSettingController@status')->name('admin-gs-status');

    // Note: Status and Feature routes are now in the ADMIN CATALOG ITEM SECTION above

    // GALLERY SECTION ------------

    Route::get('/gallery/show', 'Admin\GalleryController@show')->name('admin-gallery-show');
    Route::post('/gallery/store', 'Admin\GalleryController@store')->name('admin-gallery-store');
    Route::get('/gallery/delete', 'Admin\GalleryController@destroy')->name('admin-gallery-delete');

    // GALLERY SECTION ENDS------------

    Route::post('/page-settings/update/all', 'Admin\PageSettingController@update')->name('admin-ps-update');
    Route::post('/page-settings/update/home', 'Admin\PageSettingController@homeupdate')->name('admin-ps-homeupdate');
    Route::post('/page-settings/menu-update', 'Admin\PageSettingController@menuupdate')->name('admin-ps-menuupdate');

    // ------------ GLOBAL ENDS ----------------------

    Route::group(['middleware' => 'permissions:super'], function () {

        Route::get('/cache/clear', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return redirect()->route('admin.dashboard')->with('cache', 'System Cache Has Been Removed.');
        })->name('admin-cache-clear');

        Route::get('/check/movescript', 'Admin\DashboardController@movescript')->name('admin-move-script');
        Route::get('/generate/backup', 'Admin\DashboardController@generate_bkup')->name('admin-generate-backup');
        Route::get('/clear/backup', 'Admin\DashboardController@clear_bkup')->name('admin-clear-backup');

        // ------------ LICENSE SECTION ----------------------
        Route::get('/license/datatables', 'Admin\LicenseController@datatables')->name('admin-license-datatables');
        Route::get('/license', 'Admin\LicenseController@index')->name('admin-license-index');
        Route::get('/license/create', 'Admin\LicenseController@create')->name('admin-license-create');
        Route::post('/license/create', 'Admin\LicenseController@store')->name('admin-license-store');
        Route::get('/license/edit/{id}', 'Admin\LicenseController@edit')->name('admin-license-edit');
        Route::post('/license/edit/{id}', 'Admin\LicenseController@update')->name('admin-license-update');
        Route::delete('/license/delete/{id}', 'Admin\LicenseController@destroy')->name('admin-license-delete');
        Route::get('/license/activate/{id}', 'Admin\LicenseController@activateLicense')->name('admin-license-activate-license');
        Route::get('/license/deactivate/{id}', 'Admin\LicenseController@deactivate')->name('admin-license-deactivate');
        Route::get('/license/generate-key', 'Admin\LicenseController@generateKey')->name('admin-license-generate-key');
        Route::get('/activation', 'Admin\LicenseController@activation')->name('admin-activation-form');
        Route::post('/activation', 'Admin\LicenseController@activateWithKey')->name('admin-activate-purchase');
        // ------------ LICENSE SECTION ENDS ----------------------

        // ------------ ADMIN ROLE SECTION ----------------------

        Route::get('/admin-role/datatables', 'Admin\RoleController@datatables')->name('admin-role-datatables');
        Route::get('/admin-role', 'Admin\RoleController@index')->name('admin-role-index');
        Route::get('/admin-role/create', 'Admin\RoleController@create')->name('admin-role-create');
        Route::post('/admin-role/create', 'Admin\RoleController@store')->name('admin-role-store');
        Route::get('/admin-role/edit/{id}', 'Admin\RoleController@edit')->name('admin-role-edit');
        Route::post('/admin-role/edit/{id}', 'Admin\RoleController@update')->name('admin-role-update');
        Route::delete('/admin-role/delete/{id}', 'Admin\RoleController@destroy')->name('admin-role-delete');

        // ------------ ADMIN ROLE SECTION ENDS ----------------------

        // ------------ ADDON SECTION ----------------------

        Route::get('/addon/datatables', 'Admin\AddonController@datatables')->name('admin-addon-datatables');
        Route::get('/addon', 'Admin\AddonController@index')->name('admin-addon-index');
        Route::get('/addon/create', 'Admin\AddonController@create')->name('admin-addon-create');
        Route::post('/addon/install', 'Admin\AddonController@install')->name('admin-addon-install');
        Route::get('/addon/uninstall/{id}', 'Admin\AddonController@uninstall')->name('admin-addon-uninstall');

        // ------------ ADDON SECTION ENDS ----------------------

    });

});

// ************************************ ADMIN SECTION ENDS**********************************************

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
            Route::get('delivery/boy/find', 'Merchant\DeliveryController@findReider')->name('merchant.find.rider');
            Route::post('rider/search/submit', 'Merchant\DeliveryController@findReiderSubmit')->name('merchant-rider-search-submit');

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
            Route::get('/catalog-items/search-sku', 'Merchant\CatalogItemController@searchSku')->name('merchant-catalog-item-search-sku');
            Route::post('/catalog-items/store-offer', 'Merchant\CatalogItemController@storeOffer')->name('merchant-catalog-item-store-offer');
            Route::put('/catalog-items/update-offer/{merchantItemId}', 'Merchant\CatalogItemController@updateOffer')->name('merchant-catalog-item-update-offer');
            Route::get('/catalog-items/types', 'Merchant\CatalogItemController@types')->name('merchant-catalog-item-types');
            Route::get('/catalog-items/{slug}/create', 'Merchant\CatalogItemController@create')->name('merchant-catalog-item-create');
            Route::post('/catalog-items/store', 'Merchant\CatalogItemController@store')->name('merchant-catalog-item-store');
            Route::get('/getattributes', 'Merchant\CatalogItemController@getAttributes')->name('merchant-catalog-item-getattributes');
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

            //------------ MERCHANT GALLERY SECTION ------------

            Route::get('/gallery/show', 'Merchant\GalleryController@show')->name('merchant-gallery-show');
            Route::post('/gallery/store', 'Merchant\GalleryController@store')->name('merchant-gallery-store');
            Route::get('/gallery/delete', 'Merchant\GalleryController@destroy')->name('merchant-gallery-delete');

            //------------ MERCHANT GALLERY SECTION ENDS------------

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

        // USER SUBSCRIPTION

        // Subscription Package
        Route::get('/package', 'User\SubscriptionController@package')->name('user-package');
        Route::get('/subscription/{id}', 'User\SubscriptionController@vendorrequest')->name('user-vendor-request');
        Route::post('/vendor-request', 'User\SubscriptionController@vendorrequestsub')->name('user-vendor-request-submit');

        // Subscription Payment Redirect
        Route::get('/payment/cancle', 'User\SubscriptionController@paycancle')->name('user.payment.cancle');
        Route::get('/payment/return', 'User\SubscriptionController@payreturn')->name('user.payment.return');
        Route::get('/shop/check', 'User\SubscriptionController@check')->name('user.shop.check');
        // Paypal
        Route::post('/paypal-submit', 'Payment\Subscription\PaypalController@store')->name('user.paypal.submit');
        Route::get('/paypal-notify', 'Payment\Subscription\PaypalController@notify')->name('user.paypal.notify');

        // Stripe
        Route::post('/stripe-submit', 'Payment\Subscription\StripeController@store')->name('user.stripe.submit');
        Route::get('/stripe-subscription/notify', 'Payment\Subscription\StripeController@notify')->name('user.stripe.notify');

        // Instamojo
        Route::post('/instamojo-submit', 'Payment\Subscription\InstamojoController@store')->name('user.instamojo.submit');
        Route::get('/instamojo-notify', 'Payment\Subscription\InstamojoController@notify')->name('user.instamojo.notify');

        // Paystack
        Route::post('/paystack-submit', 'Payment\Subscription\PaystackController@store')->name('user.paystack.submit');

        // PayTM
        Route::post('/paytm-submit', 'Payment\Subscription\PaytmController@store')->name('user.paytm.submit');;
        Route::post('/paytm-notify', 'Payment\Subscription\PaytmController@notify')->name('user.paytm.notify');

        // Molly
        Route::post('/molly-submit', 'Payment\Subscription\MollieController@store')->name('user.molly.submit');
        Route::get('/molly-notify', 'Payment\Subscription\MollieController@notify')->name('user.molly.notify');

        // RazorPay
        Route::post('/razorpay-submit', 'Payment\Subscription\RazorpayController@store')->name('user.razorpay.submit');
        Route::post('/razorpay-notify', 'Payment\Subscription\RazorpayController@notify')->name('user.razorpay.notify');

        // Authorize.Net
        Route::post('/authorize-submit', 'Payment\Subscription\AuthorizeController@store')->name('user.authorize.submit');

        // Mercadopago
        Route::post('/mercadopago-submit', 'Payment\Subscription\MercadopagoController@store')->name('user.mercadopago.submit');

        // Flutter Wave
        Route::post('/flutter-submit', 'Payment\Subscription\FlutterwaveController@store')->name('user.flutter.submit');

        // SSLCommerz
        Route::post('/ssl-submit', 'Payment\Subscription\SslController@store')->name('user.ssl.submit');
        Route::post('/ssl-notify', 'Payment\Subscription\SslController@notify')->name('user.ssl.notify');

        // Voguepay
        Route::post('/voguepay-submit', 'Payment\Subscription\VoguepayController@store')->name('user.voguepay.submit');

        // Manual
        Route::post('/manual-submit', 'Payment\Subscription\ManualPaymentController@store')->name('user.manual.submit');

        // USER SUBSCRIPTION ENDS

        // USER DEPOSIT

        // Deposit & Transaction

        Route::get('/deposit/transactions', 'User\DepositController@transactions')->name('user-transactions-index');
        Route::get('/deposit/transactions/{id}/show', 'User\DepositController@transhow')->name('user-trans-show');
        Route::get('/deposit/index', 'User\DepositController@index')->name('user-deposit-index');
        Route::get('/deposit/create', 'User\DepositController@create')->name('user-deposit-create');

        // Subscription Payment Redirect
        Route::get('/deposit/payment/cancle', 'User\DepositController@paycancle')->name('deposit.payment.cancle');
        Route::get('/deposit/payment/return', 'User\DepositController@payreturn')->name('deposit.payment.return');

        // Paypal
        Route::post('/deposit/paypal-submit', 'Payment\Deposit\PaypalController@store')->name('deposit.paypal.submit');
        Route::get('/deposit/paypal-notify', 'Payment\Deposit\PaypalController@notify')->name('deposit.paypal.notify');

        // Stripe
        Route::post('/deposit/stripe-submit', 'Payment\Deposit\StripeController@store')->name('deposit.stripe.submit');
        Route::get('/deposit/stripe/notify', 'Payment\Deposit\StripeController@notify')->name('deposit.stripe.notify');

        // Instamojo
        Route::post('/deposit/instamojo-submit', 'Payment\Deposit\InstamojoController@store')->name('deposit.instamojo.submit');
        Route::get('/deposit/instamojo-notify', 'Payment\Deposit\InstamojoController@notify')->name('deposit.instamojo.notify');

        // Paystack
        Route::post('/deposit/paystack-submit', 'Payment\Deposit\PaystackController@store')->name('deposit.paystack.submit');

        // PayTM
        Route::post('/deposit/paytm-submit', 'Payment\Deposit\PaytmController@store')->name('deposit.paytm.submit');;
        Route::post('/deposit/paytm-notify', 'Payment\Deposit\PaytmController@notify')->name('deposit.paytm.notify');

        // Molly
        Route::post('/deposit/molly-submit', 'Payment\Deposit\MollieController@store')->name('deposit.molly.submit');
        Route::get('/deposit/molly-notify', 'Payment\Deposit\MollieController@notify')->name('deposit.molly.notify');

        // RazorPay
        Route::post('/deposit/razorpay-submit', 'Payment\Deposit\RazorpayController@store')->name('deposit.razorpay.submit');
        Route::post('/deposit/razorpay-notify', 'Payment\Deposit\RazorpayController@notify')->name('deposit.razorpay.notify');

        // Authorize.Net
        Route::post('/deposit/authorize-submit', 'Payment\Deposit\AuthorizeController@store')->name('deposit.authorize.submit');

        // Mercadopago
        Route::post('/deposit/mercadopago-submit', 'Payment\Deposit\MercadopagoController@store')->name('deposit.mercadopago.submit');

        // Flutter Wave
        Route::post('/deposit/flutter-submit', 'Payment\Deposit\FlutterwaveController@store')->name('deposit.flutter.submit');

        // SSLCommerz
        Route::post('/deposit/ssl-submit', 'Payment\Deposit\SslController@store')->name('deposit.ssl.submit');
        Route::post('/deposit/ssl-notify', 'Payment\Deposit\SslController@notify')->name('deposit.ssl.notify');

        // Voguepay
        Route::post('/deposit/voguepay-submit', 'Payment\Deposit\VoguepayController@store')->name('deposit.voguepay.submit');

        // Manual
        Route::post('/deposit/manual-submit', 'Payment\Deposit\ManualPaymentController@store')->name('deposit.manual.submit');

        // USER DEPOSIT ENDS

        // User Vendor Send Message

        Route::post('/user/contact', 'User\MessageController@usercontact')->name('user-contact');
        Route::get('/messages', 'User\MessageController@messages')->name('user-messages');
        Route::get('/message/{id}', 'User\MessageController@message')->name('user-message');
        Route::post('/message/post', 'User\MessageController@postmessage')->name('user-message-post');
        Route::get('/message/{id}/delete', 'User\MessageController@messagedelete')->name('user-message-delete');
        Route::get('/message/load/{id}', 'User\MessageController@msgload')->name('user-vendor-message-load');

        // User Vendor Send Message Ends

        // User Admin Send Message

        // Tickets
        Route::get('admin/tickets', 'User\MessageController@adminmessages')->name('user-message-index');
        // Disputes
        Route::get('admin/disputes', 'User\MessageController@adminDiscordmessages')->name('user-dmessage-index');

        Route::get('admin/message/{id}', 'User\MessageController@adminmessage')->name('user-message-show');
        Route::post('admin/message/post', 'User\MessageController@adminpostmessage')->name('user-message-store');
        Route::get('admin/message/{id}/delete', 'User\MessageController@adminmessagedelete')->name('user-message-delete1');
        Route::post('admin/user/send/message', 'User\MessageController@adminusercontact')->name('user-send-message');
        Route::get('admin/message/load/{id}', 'User\MessageController@messageload')->name('user-message-load');
        // User Admin Send Message Ends

        Route::get('/affilate/program', 'User\UserController@affilate_code')->name('user-affilate-program');
        Route::get('/affilate/history', 'User\UserController@affilate_history')->name('user-affilate-history');

        Route::get('/affilate/withdraw', 'User\WithdrawController@index')->name('user-wwt-index');
        Route::get('/affilate/withdraw/create', 'User\WithdrawController@create')->name('user-wwt-create');
        Route::post('/affilate/withdraw/create', 'User\WithdrawController@store')->name('user-wwt-store');

        // User Favorite Seller

        Route::get('/favorite/seller', 'User\UserController@favorites')->name('user-favorites');
        Route::get('/favorite/{id1}/{id2}', 'User\UserController@favorite')->name('user-favorite');
        Route::get('/favorite/seller/{id}/delete', 'User\UserController@favdelete')->name('user-favorite-delete');

        // Mobile Deposit Route section

        Route::get('/api/checkout/instamojo/notify', 'Api\User\Payment\InstamojoController@notify')->name('api.user.deposit.instamojo.notify');

        Route::post('/api/paystack/submit', 'Api\User\Payment\PaystackController@store')->name('api.user.deposit.paystack.submit');
        // Route::post('/api/voguepay/submit', 'Api\User\Payment\VoguepayController@store')->name('api.user.deposit.voguepay.submit'); // Controller file missing

        Route::post('/api/instamojo/submit', 'Api\User\Payment\InstamojoController@store')->name('api.user.deposit.instamojo.submit');
        Route::post('/api/paypal-submit', 'Api\User\Payment\PaymentController@store')->name('api.user.deposit.paypal.submit');
        Route::get('/api/paypal/notify', 'Api\User\Payment\PaymentController@notify')->name('api.user.deposit.payment.notify');
        Route::post('/api/authorize-submit', 'Api\User\Payment\AuthorizeController@store')->name('api.user.deposit.authorize.submit');

        Route::post('/api/payment/stripe-submit', 'Api\User\Payment\StripeController@store')->name('api.user.deposit.stripe.submit');
        Route::get('/api/payment/stripe/notify', 'Api\User\Payment\StripeController@notify')->name('api.user.deposit.stripe.notify');

        // ssl Routes
        Route::post('/api/ssl/submit', 'Api\User\Payment\SslController@store')->name('api.user.deposit.ssl.submit');
        Route::post('/api/ssl/notify', 'Api\User\Payment\SslController@notify')->name('api.user.deposit.ssl.notify');
        Route::post('/api/ssl/cancle', 'Api\User\Payment\SslController@cancle')->name('api.user.deposit.ssl.cancle');

        // Molly Routes
        Route::post('/api/molly/submit', 'Api\User\Payment\MollyController@store')->name('api.user.deposit.molly.submit');
        Route::get('/api/molly/notify', 'Api\User\Payment\MollyController@notify')->name('api.user.deposit.molly.notify');

        //PayTM Routes
        Route::post('/api/paytm-submit', 'Api\User\Payment\PaytmController@store')->name('api.user.deposit.paytm.submit');;
        Route::post('/api/paytm-callback', 'Api\User\Payment\PaytmController@paytmCallback')->name('api.user.deposit.paytm.notify');

        //RazorPay Routes
        Route::post('/api/razorpay-submit', 'Api\User\Payment\RazorpayController@store')->name('api.user.deposit.razorpay.submit');;
        Route::post('/api/razorpay-callback', 'Api\User\Payment\RazorpayController@razorCallback')->name('api.user.deposit.razorpay.notify');

        // Mercadopago Routes
        Route::get('/api/checkout/mercadopago/return', 'Api\User\Payment\MercadopagoController@payreturn')->name('api.user.deposit.mercadopago.return');
        Route::post('/api/checkout/mercadopago/notify', 'Api\User\Payment\MercadopagoController@notify')->name('api.user.deposit.mercadopago.notify');
        Route::post('/api/checkout/mercadopago/submit', 'Api\User\Payment\MercadopagoController@store')->name('api.user.deposit.mercadopago.submit');
        // Flutterwave Routes
        Route::post('/api/flutter/submit', 'Api\User\Payment\FlutterWaveController@store')->name('api.user.deposit.flutter.submit');
        Route::post('/api/flutter/notify', 'Api\User\Payment\FlutterWaveController@notify')->name('api.user.deposit.flutter.notify');

        // Mobile Deposit Route section

    });

    // ************************************ USER SECTION ENDS**********************************************

    // ************************************ RIDER SECTION ENDS**********************************************
    Route::prefix('rider')->group(function () {

        // USER AUTH SECION
        Route::get('/login', 'Rider\LoginController@showLoginForm')->name('rider.login');
        Route::post('/login', 'Auth\Rider\LoginController@login')->name('rider.login.submit');
        Route::get('/success/{status}', 'Rider\LoginController@status')->name('rider.success');

        Route::get('/register', 'Rider\RegisterController@showRegisterForm')->name('rider.register');

        // rider Register
        Route::post('/register', 'Auth\Rider\RegisterController@register')->name('rider-register-submit');
        Route::get('/register/verify/{token}', 'Auth\Rider\RegisterController@token')->name('rider-register-token');
        // rider Register End

        //------------ rider FORGOT SECTION ------------
        Route::get('/forgot', 'Auth\Rider\ForgotController@index')->name('rider.forgot');
        Route::post('/forgot', 'Auth\Rider\ForgotController@forgot')->name('rider.forgot.submit');
        Route::get('/change-password/{token}', 'Auth\Rider\ForgotController@showChangePassForm')->name('rider.change.token');
        Route::post('/change-password', 'Auth\Rider\ForgotController@changepass')->name('rider.change.password');

        //------------ USER FORGOT SECTION ENDS ------------

        Route::get('/logout', 'Rider\LoginController@logout')->name('rider-logout');
        Route::get('/dashboard', 'Rider\RiderController@index')->name('rider-dashboard');

        Route::get('/profile', 'Rider\RiderController@profile')->name('rider-profile');
        Route::post('/profile', 'Rider\RiderController@profileupdate')->name('rider-profile-update');

        Route::get('/service/area', 'Rider\RiderController@serviceArea')->name('rider-service-area');
        Route::get('/service/area/create', 'Rider\RiderController@serviceAreaCreate')->name('rider-service-area-create');
        Route::post('/service/area/create', 'Rider\RiderController@serviceAreaStore')->name('rider-service-area-store');
        Route::get('/service/area/edit/{id}', 'Rider\RiderController@serviceAreaEdit')->name('rider-service-area-edit');
        Route::post('/service/area/edit/{id}', 'Rider\RiderController@serviceAreaUpdate')->name('rider-service-area-update');
        Route::get('/service/area/delete/{id}', 'Rider\RiderController@serviceAreaDestroy')->name('rider-service-area-delete');

        Route::get('/withdraw', 'Rider\WithdrawController@index')->name('rider-wwt-index');
        Route::get('/withdraw/create', 'Rider\WithdrawController@create')->name('rider-wwt-create');
        Route::post('/withdraw/create', 'Rider\WithdrawController@store')->name('rider-wwt-store');

        Route::get('my/purchases', 'Rider\RiderController@purchases')->name('rider-purchases');
        Route::get('purchase/details/{id}', 'Rider\RiderController@purchaseDetails')->name('rider-purchase-details');
        Route::get('purchase/delivery/accept/{id}', 'Rider\RiderController@purchaseAccept')->name('rider-purchase-delivery-accept');
        Route::get('purchase/delivery/reject/{id}', 'Rider\RiderController@purchaseReject')->name('rider-purchase-delivery-reject');
        Route::get('purchase/delivery/complete/{id}', 'Rider\RiderController@purchaseComplete')->name('rider-purchase-delivery-complete');

        Route::get('/reset', 'Rider\RiderController@resetform')->name('rider-reset');
        Route::post('/reset', 'Rider\RiderController@reset')->name('rider-reset-submit');
    });

    // ************************************ RIDER SECTION ENDS**********************************************

    // ************************************ FRONT SECTION **********************************************


    Route::post('/item/report', 'Front\CatalogController@report')->name('catalog-item.report');

    Route::get('/', 'Front\FrontendController@index')->name('front.index');
    Route::get('/view', 'Front\CartController@view_cart')->name('front.cart-view');
    // Route removed - extraIndex merged into index() with section-based rendering

    Route::get('/currency/{id}', 'Front\FrontendController@currency')->name('front.currency');
    Route::get('/language/{id}', 'Front\FrontendController@language')->name('front.language');
    Route::get('/order/track/{id}', 'Front\FrontendController@trackload')->name('front.track.search');

    // SHIPMENT TRACKING SECTION
    Route::get('/tracking', 'Front\ShipmentTrackingController@index')->name('front.tracking');
    Route::get('/tracking/status', 'Front\ShipmentTrackingController@getStatus')->name('front.tracking.status');
    Route::get('/tracking/refresh', 'Front\ShipmentTrackingController@refresh')->name('front.tracking.refresh');
    Route::get('/my-shipments', 'Front\ShipmentTrackingController@myShipments')->name('front.my-shipments');
    // SHIPMENT TRACKING SECTION ENDS

    // BLOG SECTION
    Route::get('/blog', 'Front\FrontendController@blog')->name('front.blog');
    Route::get('/blog/{slug}', 'Front\FrontendController@blogshow')->name('front.blogshow');
    Route::get('/blog/category/{slug}', 'Front\FrontendController@blogcategory')->name('front.blogcategory');
    Route::get('/blog/tag/{slug}', 'Front\FrontendController@blogtags')->name('front.blogtags');
    Route::get('/blog-search', 'Front\FrontendController@blogsearch')->name('front.blogsearch');
    Route::get('/blog/archive/{slug}', 'Front\FrontendController@blogarchive')->name('front.blogarchive');
    // BLOG SECTION ENDS

    // FAQ SECTION
    Route::get('/faq', 'Front\FrontendController@faq')->name('front.faq');
    // FAQ SECTION ENDS

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
    Route::get('/category/{category?}/{subcategory?}/{childcategory?}', 'Front\CatalogController@category')->name('front.category');
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

    // COMMENT SECTION
    Route::post('/item/comment/store', 'Front\CatalogItemDetailsController@comment')->name('catalog-item.comment');
    Route::post('/item/comment/edit/{id}', 'Front\CatalogItemDetailsController@commentedit')->name('catalog-item.comment.edit');
    Route::get('/item/comment/delete/{id}', 'Front\CatalogItemDetailsController@commentdelete')->name('catalog-item.comment.delete');
    // COMMENT SECTION ENDS

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
    // ALL checkout operations MUST have explicit vendor_id in Route.
    // NO session, NO POST, NO hidden inputs for vendor context.
    // Cart is multi-vendor; Checkout is single-vendor per transaction.
    // ====================================================================

    // Vendor-specific checkout routes (with session preservation middleware)
    Route::middleware(['preserve.session'])->prefix('checkout/vendor/{vendorId}')->group(function () {
        // Step 1: Address
        Route::get('/', 'Front\CheckoutController@checkoutVendor')->name('front.checkout.vendor');
        Route::post('/step1/submit', 'Front\CheckoutController@checkoutVendorStep1')->name('front.checkout.vendor.step1.submit');
        Route::get('/step1/submit', function($vendorId) {
            return redirect()->route('front.checkout.vendor', $vendorId)->with('info', __('Please fill out the form and submit again.'));
        });

        // Step 2: Shipping
        Route::get('/step2', 'Front\CheckoutController@checkoutVendorStep2')->name('front.checkout.vendor.step2');
        Route::post('/step2/submit', 'Front\CheckoutController@checkoutVendorStep2Submit')->name('front.checkout.vendor.step2.submit');
        Route::get('/step2/submit', function($vendorId) {
            return redirect()->route('front.checkout.vendor.step2', $vendorId)->with('info', __('Please fill out the form and submit again.'));
        });

        // Step 3: Payment
        Route::get('/step3', 'Front\CheckoutController@checkoutVendorStep3')->name('front.checkout.vendor.step3');

        // ================================================================
        // PAYMENT ROUTES - All inside vendor context
        // ================================================================

        // MyFatoorah
        Route::post('/payment/myfatoorah', 'App\Http\Controllers\MyFatoorahController@index')->name('front.checkout.vendor.myfatoorah.submit');

        // Cash On Delivery
        Route::post('/payment/cod', 'Payment\Checkout\CashOnDeliveryController@store')->name('front.checkout.vendor.cod.submit');

        // Paypal
        Route::post('/payment/paypal', 'Payment\Checkout\PaypalController@store')->name('front.checkout.vendor.paypal.submit');

        // Stripe
        Route::post('/payment/stripe', 'Payment\Checkout\StripeController@store')->name('front.checkout.vendor.stripe.submit');

        // Wallet
        Route::post('/payment/wallet', 'Payment\Checkout\WalletPaymentController@store')->name('front.checkout.vendor.wallet.submit');

        // Manual
        Route::post('/payment/manual', 'Payment\Checkout\ManualPaymentController@store')->name('front.checkout.vendor.manual.submit');

        // Instamojo
        Route::post('/payment/instamojo', 'Payment\Checkout\InstamojoController@store')->name('front.checkout.vendor.instamojo.submit');

        // Paystack
        Route::post('/payment/paystack', 'Payment\Checkout\PaystackController@store')->name('front.checkout.vendor.paystack.submit');

        // PayTM
        Route::post('/payment/paytm', 'Payment\Checkout\PaytmController@store')->name('front.checkout.vendor.paytm.submit');

        // Mollie
        Route::post('/payment/mollie', 'Payment\Checkout\MollieController@store')->name('front.checkout.vendor.mollie.submit');

        // RazorPay
        Route::post('/payment/razorpay', 'Payment\Checkout\RazorpayController@store')->name('front.checkout.vendor.razorpay.submit');

        // Authorize.Net
        Route::post('/payment/authorize', 'Payment\Checkout\AuthorizeController@store')->name('front.checkout.vendor.authorize.submit');

        // Mercadopago
        Route::post('/payment/mercadopago', 'Payment\Checkout\MercadopagoController@store')->name('front.checkout.vendor.mercadopago.submit');

        // Flutter Wave
        Route::post('/payment/flutterwave', 'Payment\Checkout\FlutterwaveController@store')->name('front.checkout.vendor.flutterwave.submit');

        // SSLCommerz
        Route::post('/payment/ssl', 'Payment\Checkout\SslController@store')->name('front.checkout.vendor.ssl.submit');

        // Voguepay
        Route::post('/payment/voguepay', 'Payment\Checkout\VoguepayController@store')->name('front.checkout.vendor.voguepay.submit');

        // Location reset
        Route::post('/location/reset', 'Front\CheckoutController@resetLocation')->name('front.checkout.vendor.location.reset');

        // Discount Code (vendor-specific)
        Route::get('/discount-code/check', 'Front\DiscountCodeController@discountCodeCheck')->name('front.checkout.vendor.discount-code.check');
        Route::post('/discount-code/remove', 'Front\DiscountCodeController@removeDiscountCode')->name('front.checkout.vendor.discount-code.remove');

        // Wallet check
        Route::get('/wallet-check', 'Front\CheckoutController@walletcheck')->name('front.checkout.vendor.wallet.check');
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
    // PAYMENT NOTIFY/CALLBACK ROUTES (External - no vendor_id)
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

    // Deposit
    Route::post('/dflutter/notify', 'Payment\Deposit\FlutterwaveController@notify')->name('deposit.flutter.notify');

    // Subscription
    Route::post('/uflutter/notify', 'Payment\Subscription\FlutterwaveController@notify')->name('user.flutter.notify');

    // Checkout
    Route::post('/cflutter/notify', 'Payment\Checkout\FlutterwaveController@notify')->name('front.flutter.notify');

    // CHECKOUT SECTION ENDS

    //   Mobile Checkout section

    Route::get('/payment/checkout', 'Api\Payment\CheckoutController@checkout')->name('payment.checkout');
    Route::post('/payment/stripe-submit', 'Api\Payment\StripeController@store')->name('payment.stripe');
    Route::get('/payment/stripe-notify', 'Api\Payment\StripeController@notify')->name('payment.notify');

    Route::get('/deposit/app/payment/{slug1}/{slug2}', 'Api\Payment\CheckoutController@depositloadpayment')->name('deposit.app.payment');

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
    Route::get('/deposit/payment/{number}', 'Api\User\DepositController@sendDeposit')->name('user.deposit.send');

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


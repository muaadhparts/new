<?php

// ************************************ OPERATOR SECTION **********************************************

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


// Quick checkout - redirect to cart
Route::get('/checkout/quick', function() {
    return redirect()->route('merchant-cart.index');
})->name('front.checkout.quick');

Route::prefix('modal')->name('modal.')->group(function () {
    Route::get('/catalog-item/id/{catalogItem}',   [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.id');
    Route::get('/catalog-item/part_number/{part_number}',      [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.part_number');
    Route::get('/alternative/{key}',      [CatalogItemDetailsController::class, 'alternativeFragment'])->name('alternative');
    Route::get('/quickview/{id}',         [CatalogItemDetailsController::class, 'quickFragment'])->name('quickview');
    Route::get('/offers/{catalogItemId}', [CatalogItemDetailsController::class, 'offersFragment'])->name('offers');
    Route::get('/offers-by-part/{part_number}', [CatalogItemDetailsController::class, 'offersByPartNumber'])->name('offers-by-part');
    Route::get('/catalog-item/{key}',          [CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item');
    Route::get('/fitment/{catalogItemId}', [\App\Http\Controllers\Api\CatalogItemApiController::class, 'getFitmentDetails'])->name('fitment');
});

// ✅ API Routes with Rate Limiting
Route::prefix('api')->middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('/callouts', [CalloutController::class, 'show'])->name('api.callouts.show');
    Route::get('/callouts/html', [CalloutController::class, 'showHtml'])->name('api.callouts.html');
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

    //------------ OPERATOR UNLOCK/PROTECTION SECTION ------------
    Route::get('/unlock', 'Operator\UnlockController@show')->name('operator.unlock');
    Route::post('/unlock', 'Operator\UnlockController@verify')->name('operator.unlock.verify');
    Route::get('/lock', 'Operator\UnlockController@lock')->name('operator.lock')->middleware('auth:operator');
    //------------ OPERATOR UNLOCK SECTION ENDS ------------

    //------------ PROTECTED OPERATOR ROUTES (Require Authentication + Protection) ------------
    Route::middleware(['auth:operator', 'operator.protection'])->group(function () {

        //------------ OPERATOR CATALOG EVENT SECTION ------------
        Route::get('/all/event/count', 'Operator\CatalogEventController@allEventCount')->name('all-event-count');
        Route::get('/user/event/show', 'Operator\CatalogEventController@showUserEvents')->name('user-event-show');
        Route::get('/user/event/clear', 'Operator\CatalogEventController@clearUserEvents')->name('user-event-clear');
        Route::get('/purchase/event/show', 'Operator\CatalogEventController@showPurchaseEvents')->name('purchase-event-show');
        Route::get('/purchase/event/clear', 'Operator\CatalogEventController@clearPurchaseEvents')->name('purchase-event-clear');
        Route::get('/catalog-item/event/show', 'Operator\CatalogEventController@showCatalogItemEvents')->name('catalog-item-event-show');
        Route::get('/catalog-item/event/clear', 'Operator\CatalogEventController@clearCatalogItemEvents')->name('catalog-item-event-clear');
        Route::get('/conv/event/show', 'Operator\CatalogEventController@showConversationEvents')->name('conv-event-show');
        Route::get('/conv/event/clear', 'Operator\CatalogEventController@clearConversationEvents')->name('conv-event-clear');
        //------------ OPERATOR CATALOG EVENT SECTION ENDS ------------

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
        Route::get('/send-message', 'Operator\PurchaseController@emailsub')->name('operator-send-message'); // Alias for email modal in user/courier lists
        Route::post('/purchase/catalogItem-submit', 'Operator\PurchaseController@catalogItem_submit')->name('operator-purchase-catalogItem-submit');
        Route::get('/purchase/catalogItem-show/{id}', 'Operator\PurchaseController@catalogItem_show');
        // REMOVED: addcart, updatecart - MerchantCart class deleted
        Route::get('/purchasecart/catalogItem-edit/{id}/{itemid}/{purchaseid}', 'Operator\PurchaseController@catalogItem_edit')->name('operator-purchase-catalogItem-edit');
        Route::get('/purchasecart/catalogItem-delete/{id}/{purchaseid}', 'Operator\PurchaseController@catalogItem_delete')->name('operator-purchase-catalogItem-delete');
        // Purchase Tracking

        // CREATE PURCHASE

        Route::get('/purchase/catalog-item/datatables', 'Operator\PurchaseCreateController@datatables')->name('operator-purchase-catalog-item-datatables');
        Route::get('/purchase/create', 'Operator\PurchaseCreateController@create')->name('operator-purchase-create');
        Route::get('/purchase/catalog-item/add/{catalog_item_id}', 'Operator\PurchaseCreateController@addCatalogItem')->name('operator-purchase-catalog-item-add');
        Route::get('/purchase/catalog-item/add', 'Operator\PurchaseCreateController@purchaseStore')->name('operator.purchase.store.new');
        Route::get('/purchase/catalog-item/remove/{catalog_item_id}', 'Operator\PurchaseCreateController@removePurchaseCatalogItem')->name('operator.purchase.catalog-item.remove');
        Route::get('/purchase/create/catalog-item-show/{id}', 'Operator\PurchaseCreateController@catalog_item_show');
        // REMOVED: addcart, removecart, CreatePurchaseSubmit - MerchantCart class deleted
        Route::get('/purchase/create/user-address', 'Operator\PurchaseCreateController@userAddress');
        Route::post('/purchase/create/user-address', 'Operator\PurchaseCreateController@userAddressSubmit')->name('operator.purchase.create.user.address');
        Route::post('/purchase/create/purchase/view', 'Operator\PurchaseCreateController@viewCreatePurchase')->name('operator.purchase.create.view');

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
        Route::get('merchant/report', 'Operator\IncomeController@merchantReport')->name('operator-merchant-report');
        Route::get('commission/detailed', 'Operator\IncomeController@commissionIncomeDetailed')->name('operator-commission-detailed');
        // -------------------------- Admin Total Income Route --------------------------//
    });

    // ========================== ACCOUNTING LEDGER SYSTEM ==========================//
    Route::group(['prefix' => 'accounts'], function () {
        // Dashboard
        Route::get('/', 'Operator\AccountLedgerController@index')->name('operator.accounts.index');

        // Parties Lists
        Route::get('/merchants', 'Operator\AccountLedgerController@merchants')->name('operator.accounts.merchants');
        Route::get('/couriers', 'Operator\AccountLedgerController@couriers')->name('operator.accounts.couriers');
        Route::get('/shipping', 'Operator\AccountLedgerController@shippingProviders')->name('operator.accounts.shipping');
        Route::get('/payment', 'Operator\AccountLedgerController@paymentProviders')->name('operator.accounts.payment');

        // Party Statement
        Route::get('/party/{party}/statement', 'Operator\AccountLedgerController@partyStatement')->name('operator.accounts.party.statement');
        Route::get('/transaction/{transaction}', 'Operator\AccountLedgerController@transactionDetails')->name('operator.accounts.transaction');

        // Settlements
        Route::get('/settlements', 'Operator\AccountLedgerController@settlements')->name('operator.accounts.settlements');
        Route::get('/settlements/create', 'Operator\AccountLedgerController@createSettlementForm')->name('operator.accounts.settlements.create');
        Route::post('/settlements', 'Operator\AccountLedgerController@storeSettlement')->name('operator.accounts.settlements.store');
        Route::get('/settlements/{batch}', 'Operator\AccountLedgerController@settlementDetails')->name('operator.accounts.settlements.show');

        // Courier Settlements - تسويات المناديب
        Route::post('/settlements/courier', 'Operator\AccountLedgerController@courierSettlement')->name('operator.accounts.settlements.courier');
        Route::get('/settlements/courier/{courierId}/pending', 'Operator\AccountLedgerController@pendingSettlementsByCourier')->name('operator.accounts.settlements.courier.pending');

        // Shipping Company Settlements - تسويات شركات الشحن
        Route::post('/settlements/shipping', 'Operator\AccountLedgerController@shippingCompanySettlement')->name('operator.accounts.settlements.shipping');
        Route::get('/settlements/shipping/{providerCode}/pending', 'Operator\AccountLedgerController@pendingSettlementsByProvider')->name('operator.accounts.settlements.shipping.pending');

        // Sync Parties
        Route::post('/sync-parties', 'Operator\AccountLedgerController@syncParties')->name('operator.accounts.sync');

        // Reports
        Route::get('/reports/receivables', 'Operator\AccountLedgerController@receivablesReport')->name('operator.accounts.reports.receivables');
        Route::get('/reports/payables', 'Operator\AccountLedgerController@payablesReport')->name('operator.accounts.reports.payables');
        Route::get('/reports/shipping', 'Operator\AccountLedgerController@shippingReport')->name('operator.accounts.reports.shipping');
        Route::get('/reports/payment', 'Operator\AccountLedgerController@paymentReport')->name('operator.accounts.reports.payment');
        Route::get('/reports/tax', 'Operator\AccountLedgerController@taxReport')->name('operator.accounts.reports.tax');

        // التقارير المحسنة (من Ledger فقط)
        Route::get('/reports/platform', 'Operator\AccountLedgerController@platformReport')->name('operator.accounts.reports.platform');
        Route::get('/reports/merchants-summary', 'Operator\AccountLedgerController@merchantsSummary')->name('operator.accounts.reports.merchants-summary');
        Route::get('/reports/couriers', 'Operator\AccountLedgerController@couriersReport')->name('operator.accounts.reports.couriers');
        Route::get('/reports/shipping-companies', 'Operator\AccountLedgerController@shippingCompaniesReport')->name('operator.accounts.reports.shipping-companies');
        Route::get('/reports/receivables-payables', 'Operator\AccountLedgerController@receivablesPayablesReport')->name('operator.accounts.reports.receivables-payables');

        // كشف حساب التاجر
        Route::get('/merchant-statement/{merchantId}', 'Operator\AccountLedgerController@merchantStatement')->name('operator.accounts.merchant-statement');

        // شركات الشحن - كشف حساب مفصل
        Route::get('/shipping-companies', 'Operator\AccountLedgerController@shippingCompanyList')->name('operator.accounts.shipping-companies');
        Route::get('/shipping-company/{providerCode}/statement', 'Operator\AccountLedgerController@shippingCompanyStatement')->name('operator.accounts.shipping-company.statement');
        Route::get('/shipping-company/{providerCode}/statement/pdf', 'Operator\AccountLedgerController@shippingCompanyStatementPdf')->name('operator.accounts.shipping-company.statement.pdf');
    });
    // ========================== END ACCOUNTING LEDGER SYSTEM ==========================//

    /////////////////////////////// ////////////////////////////////////////////

    // Note: Old Category/Subcategory/Childcategory and Attribute routes removed - now using TreeCategories

    //------------ OPERATORCATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:catalog_items'], function () {
        Route::get('/catalog-items/datatables', 'Operator\CatalogItemController@datatables')->name('operator-catalog-item-datatables');
        Route::get('/catalog-items', 'Operator\CatalogItemController@index')->name('operator-catalog-item-index');
        Route::get('/catalog-items/deactive', 'Operator\CatalogItemController@deactive')->name('operator-catalog-item-deactive');

        // CREATE SECTION
        Route::get('/catalog-items/{slug}/create', 'Operator\CatalogItemController@create')->name('operator-catalog-item-create');
        Route::post('/catalog-items/store', 'Operator\CatalogItemController@store')->name('operator-catalog-item-store');

        // EDIT SECTION
        Route::get('/catalog-items/edit/{catalogItemId}', 'Operator\CatalogItemController@edit')->name('operator-catalog-item-edit');
        Route::post('/catalog-items/edit/{catalogItemId}', 'Operator\CatalogItemController@update')->name('operator-catalog-item-update');

        // DELETE SECTION
        Route::delete('/catalog-items/delete/{id}', 'Operator\CatalogItemController@destroy')->name('operator-catalog-item-delete');

        // STATUS & SETTINGS
        Route::get('/catalog-items/status/{id1}/{id2}', 'Operator\CatalogItemController@status')->name('operator-catalog-item-status');
        Route::get('/merchant-items/status/{id}/{status}', 'Operator\CatalogItemController@merchantItemStatus')->name('operator-merchant-item-status');
        Route::get('/catalog-items/settings', 'Operator\CatalogItemController@catalogItemSettings')->name('operator-gs-catalog-item-settings');
        Route::post('/catalog-items/settings/update', 'Operator\CatalogItemController@settingUpdate')->name('operator-gs-catalog-item-settings-update');

        // CATALOG ITEM IMAGES SECTION
        Route::get('/catalog-items/images', 'Operator\CatalogItemImageController@index')->name('operator-catalog-item-images');
        Route::get('/catalog-items/images/autocomplete', 'Operator\CatalogItemImageController@autocomplete')->name('operator-catalog-item-images-autocomplete');
        Route::get('/catalog-items/images/{id}', 'Operator\CatalogItemImageController@show')->name('operator-catalog-item-images-show');
        Route::post('/catalog-items/images/{id}', 'Operator\CatalogItemImageController@update')->name('operator-catalog-item-images-update');

        // MERCHANT ITEM IMAGES SECTION
        Route::get('/merchant-items/images', 'Operator\MerchantItemImageController@index')->name('operator-merchant-item-images');
        Route::get('/merchant-items/images/autocomplete', 'Operator\MerchantItemImageController@autocomplete')->name('operator-merchant-item-images-autocomplete');
        Route::get('/merchant-items/images/merchants', 'Operator\MerchantItemImageController@getMerchants')->name('operator-merchant-item-images-merchants');
        Route::get('/merchant-items/images/branches', 'Operator\MerchantItemImageController@getBranches')->name('operator-merchant-item-images-branches');
        Route::get('/merchant-items/images/quality-brands', 'Operator\MerchantItemImageController@getQualityBrands')->name('operator-merchant-item-images-quality-brands');
        Route::get('/merchant-items/images/photos/{merchant_item_id}', 'Operator\MerchantItemImageController@getPhotos')->name('operator-merchant-item-images-photos');
        Route::post('/merchant-items/images/store', 'Operator\MerchantItemImageController@store')->name('operator-merchant-item-images-store');
        Route::delete('/merchant-items/images/{id}', 'Operator\MerchantItemImageController@destroy')->name('operator-merchant-item-images-delete');
        Route::post('/merchant-items/images/order', 'Operator\MerchantItemImageController@updateOrder')->name('operator-merchant-item-images-order');
    });

    //------------ OPERATORCATALOG ITEM SECTION ENDS------------


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

        // COURIER MANAGEMENT SECTION
        Route::get('/couriers/balances', 'Operator\CourierManagementController@index')->name('operator-courier-balances');
        Route::get('/courier/{id}/details', 'Operator\CourierManagementController@show')->name('operator-courier-details');
        Route::get('/courier/{id}/unsettled', 'Operator\CourierManagementController@unsettledDeliveries')->name('operator-courier-unsettled');
        // Use /accounts/couriers for courier accounting (AccountLedgerController)
        // COURIER MANAGEMENT SECTION ENDS

    });

    //------------ OPERATORMERCHANT SECTION ------------

    Route::group(['middleware' => 'permissions:vendors'], function () {

        Route::get('/merchants/datatables', 'Operator\MerchantController@datatables')->name('operator-merchant-datatables');
        Route::get('/merchants', 'Operator\MerchantController@index')->name('operator-merchant-index');

        Route::get('/merchants/{id}/show', 'Operator\MerchantController@show')->name('operator-merchant-show');
        Route::get('/merchants/{id}/items/datatables', 'Operator\MerchantController@merchantItemsDatatables')->name('operator-merchant-items-datatables');
        Route::get('/merchants/secret/login/{id}', 'Operator\MerchantController@secret')->name('operator-merchant-secret');
        Route::get('/merchant/edit/{id}', 'Operator\MerchantController@edit')->name('operator-merchant-edit');
        Route::post('/merchant/edit/{id}', 'Operator\MerchantController@update')->name('operator-merchant-update');

        Route::get('/merchant/request-trust-badge/{id}', 'Operator\MerchantController@requestTrustBadge')->name('operator-merchant-request-trust-badge');
        Route::post('/merchant/request-trust-badge/{id}', 'Operator\MerchantController@requestTrustBadgeSubmit')->name('operator-merchant-request-trust-badge-submit');

        Route::get('/merchants/status/{id1}/{id2}', 'Operator\MerchantController@status')->name('operator-merchant-st');
        Route::delete('/merchants/delete/{id}', 'Operator\MerchantController@destroy')->name('operator-merchant-delete');

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

    //------------ MERCHANT TRUST BADGE SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_verifications'], function () {

        Route::get('/trust-badges/datatables/{status}', 'Operator\TrustBadgeController@datatables')->name('operator-trust-badge-datatables');
        Route::get('/trust-badges/{slug}', 'Operator\TrustBadgeController@index')->name('operator-trust-badge-index');
        Route::get('/trust-badges/show/attachment', 'Operator\TrustBadgeController@show')->name('operator-trust-badge-show');
        Route::get('/trust-badges/edit/{id}', 'Operator\TrustBadgeController@edit')->name('operator-trust-badge-edit');
        Route::post('/trust-badges/edit/{id}', 'Operator\TrustBadgeController@update')->name('operator-trust-badge-update');
        Route::get('/trust-badges/status/{id1}/{id2}', 'Operator\TrustBadgeController@status')->name('operator-trust-badge-status');
        Route::delete('/trust-badges/delete/{id}', 'Operator\TrustBadgeController@destroy')->name('operator-trust-badge-delete');
    });

    //------------ MERCHANT TRUST BADGE SECTION ENDS ------------

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

        //------------ OPERATORSHIPPING ------------

        Route::get('/shipping/datatables', 'Operator\ShippingController@datatables')->name('operator-shipping-datatables');
        Route::get('/shipping', 'Operator\ShippingController@index')->name('operator-shipping-index');
        Route::get('/shipping/create', 'Operator\ShippingController@create')->name('operator-shipping-create');
        Route::post('/shipping/create', 'Operator\ShippingController@store')->name('operator-shipping-store');
        Route::get('/shipping/edit/{id}', 'Operator\ShippingController@edit')->name('operator-shipping-edit');
        Route::post('/shipping/edit/{id}', 'Operator\ShippingController@update')->name('operator-shipping-update');
        Route::delete('/shipping/delete/{id}', 'Operator\ShippingController@destroy')->name('operator-shipping-delete');

        //------------ OPERATORSHIPPING ENDS ------------

    });

    //------------ OPERATORGENERAL SETTINGS SECTION ENDS ------------

    //------------ OPERATORHOME PAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:home_page_settings'], function () {

        Route::get('/home-page-settings', 'Operator\MuaadhSettingController@homepage')->name('operator-home-page-index');


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

        //------------ OPERATOR ALTERNATIVES SECTION ------------

        Route::get('/alternatives', 'Operator\AlternativeController@index')->name('operator-alternative-index');
        Route::get('/alternatives/search', 'Operator\AlternativeController@search')->name('operator-alternative-search');
        Route::get('/alternatives/stats', 'Operator\AlternativeController@stats')->name('operator-alternative-stats');
        Route::post('/alternatives/add', 'Operator\AlternativeController@addAlternative')->name('operator-alternative-add');
        Route::post('/alternatives/remove', 'Operator\AlternativeController@removeAlternative')->name('operator-alternative-remove');

        //------------ OPERATOR ALTERNATIVES SECTION ENDS ------------

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
        //------------ OPERATORPAGE SECTION ENDS------------

        Route::get('/frontend-setting/contact', 'Operator\FrontendSettingController@contact')->name('operator-fs-contact');
        Route::post('/frontend-setting/update/all', 'Operator\FrontendSettingController@update')->name('operator-fs-update');
    });

    //------------ OPERATORMENU PAGE SETTINGS SECTION ENDS ------------

    //------------ OPERATOREMAIL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:email_settings'], function () {

        Route::get('/comms-blueprints/datatables', 'Operator\CommsBlueprintController@datatables')->name('operator-mail-datatables');
        Route::get('/comms-blueprints', 'Operator\CommsBlueprintController@index')->name('operator-mail-index');
        Route::get('/comms-blueprints/{id}', 'Operator\CommsBlueprintController@edit')->name('operator-mail-edit');
        Route::post('/comms-blueprints/{id}', 'Operator\CommsBlueprintController@update')->name('operator-mail-update');
        Route::get('/email-config', 'Operator\CommsBlueprintController@config')->name('operator-mail-config');
        Route::get('/groupemail', 'Operator\CommsBlueprintController@groupemail')->name('operator-group-show');
        Route::post('/groupemailpost', 'Operator\CommsBlueprintController@groupemailpost')->name('operator-group-submit');
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

        // Monetary Unit Settings

        // MULTIPLE MONETARY UNITS

        Route::get('/monetary-unit/datatables', 'Operator\MonetaryUnitController@datatables')->name('operator-monetary-unit-datatables'); //JSON REQUEST
        Route::get('/monetary-unit', 'Operator\MonetaryUnitController@index')->name('operator-monetary-unit-index');
        Route::get('/monetary-unit/create', 'Operator\MonetaryUnitController@create')->name('operator-monetary-unit-create');
        Route::post('/monetary-unit/create', 'Operator\MonetaryUnitController@store')->name('operator-monetary-unit-store');
        Route::get('/monetary-unit/edit/{id}', 'Operator\MonetaryUnitController@edit')->name('operator-monetary-unit-edit');
        Route::post('/monetary-unit/update/{id}', 'Operator\MonetaryUnitController@update')->name('operator-monetary-unit-update');
        Route::delete('/monetary-unit/delete/{id}', 'Operator\MonetaryUnitController@destroy')->name('operator-monetary-unit-delete');
        Route::get('/monetary-unit/status/{id1}/{id2}', 'Operator\MonetaryUnitController@status')->name('operator-monetary-unit-status');

    });

    //------------ OPERATORPAYMENT SETTINGS SECTION ENDS------------

    //------------ OPERATORSOCIAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:social_settings'], function () {

        //------------ OPERATOR NETWORK PRESENCE ------------

        Route::get('/network-presence/datatables', 'Operator\NetworkPresenceController@datatables')->name('operator-network-presence-datatables'); //JSON REQUEST
        Route::get('/network-presence', 'Operator\NetworkPresenceController@index')->name('operator-network-presence-index');
        Route::get('/network-presence/create', 'Operator\NetworkPresenceController@create')->name('operator-network-presence-create');
        Route::post('/network-presence/create', 'Operator\NetworkPresenceController@store')->name('operator-network-presence-store');
        Route::get('/network-presence/edit/{id}', 'Operator\NetworkPresenceController@edit')->name('operator-network-presence-edit');
        Route::post('/network-presence/edit/{id}', 'Operator\NetworkPresenceController@update')->name('operator-network-presence-update');
        Route::delete('/network-presence/delete/{id}', 'Operator\NetworkPresenceController@destroy')->name('operator-network-presence-delete');
        Route::get('/network-presence/status/{id1}/{id2}', 'Operator\NetworkPresenceController@status')->name('operator-network-presence-status');

        //------------ OPERATOR NETWORK PRESENCE ENDS ------------

        //------------ OPERATOR CONNECT CONFIG (OAuth Settings) ------------
        Route::get('/connect-config', 'Operator\ConnectConfigController@index')->name('operator-connect-config-index');
        Route::post('/connect-config/update', 'Operator\ConnectConfigController@socialupdate')->name('operator-connect-config-update');
        Route::post('/connect-config/update/all', 'Operator\ConnectConfigController@socialupdateall')->name('operator-connect-config-update-all');
        Route::get('/connect-config/facebook', 'Operator\ConnectConfigController@facebook')->name('operator-connect-config-facebook');
        Route::get('/connect-config/google', 'Operator\ConnectConfigController@google')->name('operator-connect-config-google');
        Route::get('/connect-config/facebook/{status}', 'Operator\ConnectConfigController@facebookup')->name('operator-connect-config-facebookup');
        Route::get('/connect-config/google/{status}', 'Operator\ConnectConfigController@googleup')->name('operator-connect-config-googleup');
        //------------ OPERATOR CONNECT CONFIG ENDS ------------
    });
    //------------ OPERATOR CONNECT CONFIG SECTION ENDS------------

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

    //------------ADMIN TYPEFACE SECTION------------------
    Route::get('/typefaces/datatables', 'Operator\TypefaceController@datatables')->name('operator.typefaces.datatables');
    Route::get('/typefaces', 'Operator\TypefaceController@index')->name('operator.typefaces.index');
    Route::get('/typefaces/create', 'Operator\TypefaceController@create')->name('operator.typefaces.create');
    Route::post('/typefaces/create', 'Operator\TypefaceController@store')->name('operator.typefaces.store');
    Route::get('/typefaces/edit/{id}', 'Operator\TypefaceController@edit')->name('operator.typefaces.edit');
    Route::post('/typefaces/edit/{id}', 'Operator\TypefaceController@update')->name('operator.typefaces.update');
    Route::delete('/typefaces/delete/{id}', 'Operator\TypefaceController@destroy')->name('operator.typefaces.delete');
    Route::get('/typefaces/status/{id}', 'Operator\TypefaceController@status')->name('operator.typefaces.status');
    //------------ADMIN TYPEFACE SECTION------------------

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
    Route::post('/general-settings/update/theme', 'Operator\MuaadhSettingController@updateTheme')->name('operator-gs-update-theme');
    Route::post('/general-settings/update/payment', 'Operator\MuaadhSettingController@generalupdatepayment')->name('operator-gs-update-payment');
    Route::post('/general-settings/update/mail', 'Operator\MuaadhSettingController@generalMailUpdate')->name('operator-gs-update-mail');
    Route::get('/general-settings/status/{field}/{status}', 'Operator\MuaadhSettingController@status')->name('operator-gs-status');

    // Note: Status and Feature routes are now in the ADMIN CATALOG ITEM SECTION above

    Route::post('/frontend-setting/update/all', 'Operator\FrontendSettingController@update')->name('operator-fs-update');

    // ------------ GLOBAL ENDS ----------------------

    Route::group(['middleware' => 'permissions:super'], function () {

        Route::get('/cache/clear', function () {
            Artisan::call('cache:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            return redirect()->route('operator.dashboard')->with('cache', 'System Cache Has Been Removed.');
        })->name('operator-cache-clear');



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

        // ============ BASIC MERCHANT ACCESS (Trusted & Untrusted) ============
        // هذه المسارات متاحة لجميع التجار بما في ذلك غير الموثقين
        Route::group(['middleware' => 'merchant'], function () {

            // MERCHANT DASHBOARD - يمكن لجميع التجار رؤية الداشبورد
            Route::get('/dashboard', 'Merchant\MerchantController@index')->name('merchant.dashboard');

            // TRUST BADGE - يمكن لجميع التجار رفع مستندات التوثيق
            Route::get('/trust-badge', 'Merchant\MerchantController@trustBadge')->name('merchant-trust-badge');
            Route::get('/warning/trust-badge/{id}', 'Merchant\MerchantController@warningTrustBadge')->name('merchant-warning');
            Route::post('/trust-badge', 'Merchant\MerchantController@trustBadgeSubmit')->name('merchant-trust-badge-submit');

            // PROFILE - يمكن لجميع التجار رؤية وتعديل بروفايلهم
            Route::get('/profile', 'Merchant\MerchantController@profile')->name('merchant-profile');
            Route::post('/profile', 'Merchant\MerchantController@profileupdate')->name('merchant-profile-update');

            // MERCHANT LOGO - شعار التاجر للفواتير
            Route::get('/logo', 'Merchant\MerchantController@logo')->name('merchant-logo');
            Route::post('/logo', 'Merchant\MerchantController@logoUpdate')->name('merchant-logo-update');
            Route::delete('/logo', 'Merchant\MerchantController@logoDelete')->name('merchant-logo-delete');
        });

        // ============ TRUSTED MERCHANT ONLY ============
        // هذه المسارات متاحة فقط للتجار الموثقين (is_merchant = 2)
        Route::group(['middleware' => ['merchant', 'trusted.merchant']], function () {

            //------------ PURCHASE SECTION ------------

            Route::get('/purchases/datatables', 'Merchant\PurchaseController@datatables')->name('merchant-purchase-datatables');
            Route::get('/purchases', 'Merchant\PurchaseController@index')->name('merchant-purchase-index');
            Route::get('/purchase/{id}/show', 'Merchant\PurchaseController@show')->name('merchant-purchase-show');
            Route::get('/purchase/{id}/invoice', 'Merchant\PurchaseController@invoice')->name('merchant-purchase-invoice');
            Route::get('/purchase/{id}/print', 'Merchant\PurchaseController@printpage')->name('merchant-purchase-print');
            Route::get('/purchase/{id1}/status/{status}', 'Merchant\PurchaseController@status')->name('merchant-purchase-status');
            Route::post('/purchase/email/', 'Merchant\PurchaseController@emailsub')->name('merchant-purchase-emailsub');

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
            Route::post('delivery/ready-for-courier', 'Merchant\DeliveryController@markReadyForCourierCollection')->name('merchant.ready.courier');
            Route::post('delivery/handover-to-courier', 'Merchant\DeliveryController@confirmHandoverToCourier')->name('merchant.handover.courier');
            Route::get('delivery/stats', 'Merchant\DeliveryController@shippingStats')->name('merchant.shipping.stats');
            Route::get('delivery/purchase-status/{purchaseId}', 'Merchant\DeliveryController@getPurchaseShipmentStatus')->name('merchant.purchase.shipment.status');

            // Dynamic Shipping Provider Routes
            Route::get('delivery/shipping-providers', 'Merchant\DeliveryController@getShippingProviders')->name('merchant.shipping.providers');
            Route::get('delivery/provider-options', 'Merchant\DeliveryController@getProviderShippingOptions')->name('merchant.provider.shipping.options');
            Route::post('delivery/send-provider-shipping', 'Merchant\DeliveryController@sendProviderShipping')->name('merchant.send.provider.shipping');
            Route::get('delivery/couriers', 'Merchant\DeliveryController@findCourier')->name('merchant.delivery.couriers');
            Route::get('delivery/merchant-branches', 'Merchant\DeliveryController@getMerchantBranches')->name('merchant.delivery.branches');

            //------------ MERCHANT CATALOG ITEM SECTION ------------

            Route::get('/catalog-items/datatables', 'Merchant\CatalogItemController@datatables')->name('merchant-catalog-item-datatables');
            Route::get('/catalog-items', 'Merchant\CatalogItemController@index')->name('merchant-catalog-item-index');

            // CREATE SECTION
            Route::get('/catalog-items/search-item', 'Merchant\CatalogItemController@searchItem')->name('merchant-catalog-item-search-item');
            Route::get('/catalog-items/{slug}/create', 'Merchant\CatalogItemController@create')->name('merchant-catalog-item-create');
            Route::post('/catalog-items/store', 'Merchant\CatalogItemController@store')->name('merchant-catalog-item-store');

            // EDIT SECTION
            Route::get('/catalog-items/edit/{merchantItemId}', 'Merchant\CatalogItemController@edit')->name('merchant-catalog-item-edit');
            Route::post('/catalog-items/edit/{merchantItemId}', 'Merchant\CatalogItemController@update')->name('merchant-catalog-item-update');

            // STATUS SECTION
            Route::get('/catalog-items/status/{id1}/{id2}', 'Merchant\CatalogItemController@status')->name('merchant-catalog-item-status');

            // DELETE SECTION
            Route::delete('/catalog-items/delete/{id}', 'Merchant\CatalogItemController@destroy')->name('merchant-catalog-item-delete');

            //------------ MERCHANT CATALOG ITEM SECTION ENDS------------

            //------------ STOCK MANAGEMENT SECTION (Merchant #1 only) ------------
            Route::get('/stock/management', 'Merchant\StockManagementController@index')->name('merchant-stock-management');
            Route::get('/stock/datatables', 'Merchant\StockManagementController@datatables')->name('merchant-stock-datatables');
            Route::get('/stock/export', 'Merchant\StockManagementController@export')->name('merchant-stock-export');
            Route::get('/stock/download/{id}', 'Merchant\StockManagementController@download')->name('merchant-stock-download');
            Route::post('/stock/full-refresh', 'Merchant\StockManagementController@triggerFullRefresh')->name('merchant-stock-full-refresh');
            Route::post('/stock/process-full-refresh', 'Merchant\StockManagementController@processFullRefresh')->name('merchant-stock-process-full-refresh');
            Route::get('/stock/progress/{id}', 'Merchant\StockManagementController@getUpdateProgress')->name('merchant-stock-progress');
            //------------ STOCK MANAGEMENT SECTION ENDS ------------

            //------------ MERCHANT PHOTO SECTION ------------

            Route::get('/merchant-photo/show', 'Merchant\MerchantPhotoController@show')->name('merchant-merchant-photo-show');
            Route::post('/merchant-photo/store', 'Merchant\MerchantPhotoController@store')->name('merchant-merchant-photo-store');
            Route::get('/merchant-photo/delete', 'Merchant\MerchantPhotoController@destroy')->name('merchant-merchant-photo-delete');

            //------------ MERCHANT PHOTO SECTION ENDS------------

            //------------ MERCHANT MY ITEM IMAGES SECTION ------------
            Route::get('/my-items/images', 'Merchant\MyItemImageController@index')->name('merchant-my-item-images');
            Route::get('/my-items/images/datatables', 'Merchant\MyItemImageController@datatables')->name('merchant-my-item-images-datatables');
            Route::get('/my-items/images/{id}', 'Merchant\MyItemImageController@show')->name('merchant-my-item-images-show');
            Route::post('/my-items/images', 'Merchant\MyItemImageController@store')->name('merchant-my-item-images-store');
            Route::post('/my-items/images/{id}', 'Merchant\MyItemImageController@update')->name('merchant-my-item-images-update');
            Route::delete('/my-items/images/{id}', 'Merchant\MyItemImageController@destroy')->name('merchant-my-item-images-delete');
            //------------ MERCHANT MY ITEM IMAGES SECTION ENDS------------

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

            //------------ MERCHANT CATALOG EVENT SECTION ------------

            Route::get('/purchase/event/show/{id}', 'Merchant\CatalogEventController@showPurchaseEvents')->name('merchant-purchase-event-show');
            Route::get('/purchase/event/count/{id}', 'Merchant\CatalogEventController@countPurchaseEvents')->name('merchant-purchase-event-count');
            Route::get('/purchase/event/clear/{id}', 'Merchant\CatalogEventController@clearPurchaseEvents')->name('merchant-purchase-event-clear');

            //------------ MERCHANT CATALOG EVENT SECTION ENDS ------------

            // Merchant Shipping Cost
            Route::get('/banner', 'Merchant\MerchantController@banner')->name('merchant-banner');

            // Merchant Social
            Route::get('/social', 'Merchant\MerchantController@social')->name('merchant-social-index');
            Route::post('/social/update', 'Merchant\MerchantController@socialupdate')->name('merchant-social-update');

            Route::get('/withdraw/datatables', 'Merchant\WithdrawController@datatables')->name('merchant-wt-datatables');
            Route::get('/withdraw', 'Merchant\WithdrawController@index')->name('merchant-wt-index');
            Route::get('/withdraw/create', 'Merchant\WithdrawController@create')->name('merchant-wt-create');
            Route::post('/withdraw/create', 'Merchant\WithdrawController@store')->name('merchant-wt-store');

            //------------ MERCHANT BRANCH (Warehouse/Origin) ------------
            Route::get('/branch/datatables', 'Merchant\MerchantBranchController@datatables')->name('merchant-branch-datatables');
            Route::get('/branch', 'Merchant\MerchantBranchController@index')->name('merchant-branch-index');
            Route::get('/branch/create', 'Merchant\MerchantBranchController@create')->name('merchant-branch-create');
            Route::post('/branch/create', 'Merchant\MerchantBranchController@store')->name('merchant-branch-store');
            Route::get('/branch/edit/{id}', 'Merchant\MerchantBranchController@edit')->name('merchant-branch-edit');
            Route::post('/branch/edit/{id}', 'Merchant\MerchantBranchController@update')->name('merchant-branch-update');
            Route::get('/branch/delete/{id}', 'Merchant\MerchantBranchController@destroy')->name('merchant-branch-delete');
            Route::get('/branch/status/{id}/{status}', 'Merchant\MerchantBranchController@status')->name('merchant-branch-status');
            Route::get('/branch/cities', 'Merchant\MerchantBranchController@getCitiesByCountry')->name('merchant-branch-get-cities');

            //------------ MERCHANT BRANCH END ------------

            //------------ MERCHANT NETWORK PRESENCE ------------

            Route::get('/network-presence/datatables', 'Merchant\NetworkPresenceController@datatables')->name('merchant-network-presence-datatables'); //JSON REQUEST
            Route::get('/network-presence', 'Merchant\NetworkPresenceController@index')->name('merchant-network-presence-index');
            Route::get('/network-presence/create', 'Merchant\NetworkPresenceController@create')->name('merchant-network-presence-create');
            Route::post('/network-presence/create', 'Merchant\NetworkPresenceController@store')->name('merchant-network-presence-store');
            Route::get('/network-presence/edit/{id}', 'Merchant\NetworkPresenceController@edit')->name('merchant-network-presence-edit');
            Route::post('/network-presence/edit/{id}', 'Merchant\NetworkPresenceController@update')->name('merchant-network-presence-update');
            Route::delete('/network-presence/delete/{id}', 'Merchant\NetworkPresenceController@destroy')->name('merchant-network-presence-delete');
            Route::get('/network-presence/status/{id1}/{id2}', 'Merchant\NetworkPresenceController@status')->name('merchant-network-presence-status');

            //------------ MERCHANT NETWORK PRESENCE ENDS ------------

            //------------ MERCHANT SHIPMENT TRACKING (NEW SYSTEM) ------------

            Route::get('/shipment-tracking', 'Merchant\ShipmentTrackingController@index')->name('merchant.shipment-tracking.index');
            Route::get('/shipment-tracking/{purchaseId}', 'Merchant\ShipmentTrackingController@show')->name('merchant.shipment-tracking.show');
            Route::put('/shipment-tracking/{purchaseId}', 'Merchant\ShipmentTrackingController@updateStatus')->name('merchant.shipment-tracking.update');
            Route::post('/shipment-tracking/{purchaseId}/start', 'Merchant\ShipmentTrackingController@startManualShipment')->name('merchant.shipment-tracking.start');
            Route::get('/shipment-tracking/{purchaseId}/refresh', 'Merchant\ShipmentTrackingController@refreshFromApi')->name('merchant.shipment-tracking.refresh');
            Route::get('/shipment-tracking/{purchaseId}/history', 'Merchant\ShipmentTrackingController@getHistory')->name('merchant.shipment-tracking.history');
            Route::get('/shipment-tracking-stats', 'Merchant\ShipmentTrackingController@stats')->name('merchant.shipment-tracking.stats');

            //------------ MERCHANT SHIPMENT TRACKING ENDS ------------

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
            Route::get('tax-report', "Merchant\IncomeController@taxReport")->name('merchant.tax-report');
            Route::get('statement', "Merchant\IncomeController@statement")->name('merchant.statement');
            Route::get('statement/pdf', "Merchant\IncomeController@statementPdf")->name('merchant.statement.pdf');
            Route::get('monthly-ledger', "Merchant\IncomeController@monthlyLedger")->name('merchant.monthly-ledger');
            Route::get('monthly-ledger/pdf', "Merchant\IncomeController@monthlyLedgerPdf")->name('merchant.monthly-ledger.pdf');
            Route::get('payouts', "Merchant\IncomeController@payouts")->name('merchant.payouts');

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

        Route::get('/logout', 'User\LoginController@logout')->name('user-logout');
        Route::get('/dashboard', 'User\UserController@index')->name('user-dashboard');

        // Merchant Application (for regular users to become merchants)
        Route::get('/apply-merchant', 'User\UserController@applyMerchant')->name('user.apply-merchant');
        Route::post('/apply-merchant', 'User\UserController@submitMerchantApplication')->name('user.apply-merchant-submit');

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
        // Get cities by country (states removed) - moved to GeocodingController
        Route::get('/country/wise/city/{country_id}', [\App\Http\Controllers\Api\GeocodingController::class, 'getCitiesByCountry'])->name('country.wise.city');
        Route::get('/user/country/wise/city', [\App\Http\Controllers\Api\GeocodingController::class, 'getCitiesByCountry'])->name('country.wise.city.user');

        // User Favorites
        Route::get('/favorites', 'User\FavoriteController@favorites')->name('user-favorites');

        Route::get('/favorite/add/merchant/{merchantItemId}', 'User\FavoriteController@add')->name('user-favorite-add-merchant');
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
        Route::post('/purchase/{id}/confirm-delivery', 'User\PurchaseController@confirmDeliveryReceipt')->name('user-confirm-delivery');

        // User Purchases Ends

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
        Route::get('/service/area/toggle-status/{id}', 'Courier\CourierController@serviceAreaToggleStatus')->name('courier-service-area-toggle-status');
        Route::get('/service/area/cities', 'Courier\CourierController@getCitiesByCountry')->name('courier-get-cities');

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

        // Financial & Accounting Routes
        Route::get('/transactions', 'Courier\CourierController@transactions')->name('courier-transactions');
        Route::get('/settlements', 'Courier\CourierController@settlements')->name('courier-settlements');
        Route::get('/financial-report', 'Courier\CourierController@financialReport')->name('courier-financial-report');
    });

    // ************************************ COURIER SECTION ENDS**********************************************

    // ************************************ FRONT SECTION **********************************************


    Route::post('/item/report', 'Front\CatalogController@report')->name('catalog-item.report');

    Route::get('/', 'Front\FrontendController@index')->name('front.index');
    // Route removed - extraIndex merged into index() with section-based rendering

    Route::get('/monetary-unit/{id}', 'Front\FrontendController@monetaryUnit')->name('front.monetary-unit');
    Route::get('/language/{id}', 'Front\FrontendController@language')->name('front.language');
    Route::get('/purchase/track/{id}', 'Front\FrontendController@trackload')->name('front.track.search');

    // SHIPMENT TRACKING SECTION
    // SHIPMENT TRACKING (NEW SYSTEM) - Public tracking page
    Route::get('/tracking', 'User\ShipmentTrackingController@track')->name('front.tracking');
    Route::get('/tracking/status', 'User\ShipmentTrackingController@getStatus')->name('front.tracking.status');

    // User shipment tracking (requires auth)
    Route::middleware('auth')->group(function() {
        Route::get('/my-shipments', 'User\ShipmentTrackingController@index')->name('user.shipment-tracking.index');
        Route::get('/my-shipments/{purchaseId}', 'User\ShipmentTrackingController@show')->name('user.shipment-tracking.show');
    });
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
    // Structure: /brands/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}
    // - brand = Brand slug (e.g., "nissan")
    // - catalog = Catalog slug (e.g., "safari-patrol-1997")
    // - cat1/cat2/cat3 = NewCategory slugs (levels 1, 2, 3)
    Route::get('/brands/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}', 'Front\CatalogController@catalog')->name('front.catalog');

    // AJAX APIs for catalog selector (lightweight on-demand loading)
    Route::get('/api/catalog/catalogs', 'Front\CatalogController@getCatalogs')->name('front.api.catalogs');
    Route::get('/api/catalog/tree', 'Front\CatalogController@getTreeCategories')->name('front.api.tree');

    // AJAX API for merchant branches (branches are fetched per merchant context only)
    Route::get('/api/merchant/branches', 'Front\CatalogController@getMerchantBranches')->name('front.api.merchant.branches');
    // CATALOG SECTION ENDS

    // COMPARE SECTION
    Route::get('/item/compare/view', 'Front\CompareController@compare')->name('catalog-item.compare');
    Route::get('/compare/add/merchant/{merchantItemId}', 'Front\CompareController@addMerchantCompare')->name('merchant.compare.add');
    Route::get('/compare/remove/merchant/{merchantItemId}', 'Front\CompareController@removeMerchantCompare')->name('merchant.compare.remove');
    Route::get('/item/compare/add/merchant/{merchantItemId}', 'Front\CompareController@addcompare')->name('catalog-item.compare.add.merchant');
    Route::get('/item/compare/remove/{merchantItemId}', 'Front\CompareController@removecompare')->name('catalog-item.compare.remove');
    // COMPARE SECTION ENDS

    // SEARCH RESULTS PAGE - Shows catalog items matching search query
    // Displays cards with offers button and alternatives (like tree view)
    Route::get('/search', 'Front\SearchResultsController@index')->name('front.search-results');

    // PART RESULT PAGE - Shows all offers for a part number
    // NEW: CatalogItem-first approach (one page per part_number, not per merchant_item)
    Route::get('/result/{part_number}', 'Front\PartResultController@show')->name('front.part-result');

    // ============ NEW MERCHANT CART SYSTEM (v4) ============
    // Clean, unified cart API - replaces all old cart routes
    // Uses: App\Http\Controllers\Front\MerchantCartController
    // Service: App\Services\Cart\MerchantCartManager
    // ALL operations are Branch-Scoped (except add which infers branch from item)
    Route::prefix('merchant-cart')->name('merchant-cart.')->group(function () {
        // Cart page view (grouped by branch)
        Route::get('/', 'Front\MerchantCartController@index')->name('index');

        // Get all branches cart (AJAX for full page)
        Route::get('/all', 'Front\MerchantCartController@all')->name('all');

        // Get branch cart summary (AJAX) - requires branch_id
        Route::get('/summary', 'Front\MerchantCartController@summary')->name('summary');

        // Cart count (for header badge)
        Route::get('/count', 'Front\MerchantCartController@count')->name('count');

        // Get branch IDs in cart
        Route::get('/branches', 'Front\MerchantCartController@branches')->name('branches');

        // Get merchant IDs in cart (legacy support)
        Route::get('/merchants', 'Front\MerchantCartController@merchants')->name('merchants');

        // Add item to cart (branch inferred from merchant_item_id)
        Route::post('/add', 'Front\MerchantCartController@add')->name('add');

        // Update item quantity - requires branch_id
        Route::post('/update', 'Front\MerchantCartController@update')->name('update');

        // Increase/Decrease quantity - requires branch_id
        Route::post('/increase', 'Front\MerchantCartController@increase')->name('increase');
        Route::post('/decrease', 'Front\MerchantCartController@decrease')->name('decrease');

        // Remove item - requires branch_id
        Route::delete('/remove/{key}', 'Front\MerchantCartController@remove')->name('remove');
        Route::post('/remove', 'Front\MerchantCartController@remove')->name('remove.post');

        // Clear branch items - requires branch_id
        Route::post('/clear-branch', 'Front\MerchantCartController@clearBranch')->name('clear-branch');

        // Clear all cart
        Route::post('/clear', 'Front\MerchantCartController@clear')->name('clear');
    });
    // ============ END NEW MERCHANT CART SYSTEM ============

    // FAVORITE SECTION
    Route::middleware('auth')->group(function () {
        Route::get('/favorite/add/merchant/{merchantItemId}', 'User\FavoriteController@addMerchantFavorite')->name('merchant.favorite.add');
        Route::get('/favorite/remove/merchant/{merchantItemId}', 'User\FavoriteController@removeMerchantFavorite')->name('merchant.favorite.remove');
    });
    // FAVORITE SECTION ENDS

    // ====================================================================
    // CHECKOUT SECTION - BRANCH CHECKOUT SYSTEM
    // ====================================================================
    // All checkout routes are now in: routes/merchant-checkout.php
    // Route prefix: /branch/{branchId}/checkout/
    // See: App\Http\Controllers\Merchant\CheckoutMerchantController
    // ====================================================================

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
    // Controllers: App\Http\Controllers\Merchant\Payment\*
    // ====================================================================
    Route::get('/checkout/payment/myfatoorah/notify', [\App\Http\Controllers\Merchant\Payment\MyFatoorahPaymentController::class, 'notify'])->name('front.myfatoorah.notify');
    Route::get('/checkout/payment/paypal-notify', [\App\Http\Controllers\Merchant\Payment\PayPalPaymentController::class, 'notify'])->name('front.paypal.notify');
    Route::get('/payment/stripe/notify', [\App\Http\Controllers\Merchant\Payment\StripePaymentController::class, 'notify'])->name('front.stripe.notify');
    Route::get('/checkout/payment/instamojo-notify', [\App\Http\Controllers\Merchant\Payment\InstamojoPaymentController::class, 'notify'])->name('front.instamojo.notify');
    Route::post('/checkout/payment/paytm-notify', [\App\Http\Controllers\Merchant\Payment\PaytmPaymentController::class, 'notify'])->name('front.paytm.notify');
    Route::get('/checkout/payment/molly-notify', [\App\Http\Controllers\Merchant\Payment\MolliePaymentController::class, 'notify'])->name('front.molly.notify');
    Route::post('/checkout/payment/razorpay-notify', [\App\Http\Controllers\Merchant\Payment\RazorpayPaymentController::class, 'notify'])->name('front.razorpay.notify');
    Route::post('/checkout/payment/ssl-notify', [\App\Http\Controllers\Merchant\Payment\SslCommerzPaymentController::class, 'notify'])->name('front.ssl.notify');

    // Payment return/cancel (legacy redirect - branch_id from session)
    Route::get('/checkout/payment/return', function() {
        $branchId = session('checkout_branch_id', 0);
        if ($branchId) {
            return redirect()->route('branch.checkout.return', ['branchId' => $branchId, 'status' => 'success']);
        }
        return redirect()->route('merchant-cart.index')->with('success', __('Payment completed'));
    })->name('front.payment.return');
    Route::get('/checkout/payment/cancle', function() {
        $branchId = session('checkout_branch_id', 0);
        if ($branchId) {
            return redirect()->route('branch.checkout.return', ['branchId' => $branchId, 'status' => 'cancelled']);
        }
        return redirect()->route('merchant-cart.index')->with('error', __('Payment cancelled'));
    })->name('front.payment.cancle');

    // CSRF Token refresh endpoint
    Route::get('/csrf-token', function() {
        return response()->json(['token' => csrf_token()]);
    })->name('csrf.token');

    // Tryoto Webhook (external - no auth)
    Route::post('/webhooks/tryoto', 'App\Http\Controllers\TryotoWebhookController@handle')->name('webhooks.tryoto');
    Route::get('/webhooks/tryoto/test', 'App\Http\Controllers\TryotoWebhookController@test')->name('webhooks.tryoto.test');

    // Flutterwave Notify Routes
    // Checkout
    Route::post('/cflutter/notify', [\App\Http\Controllers\Merchant\Payment\FlutterwavePaymentController::class, 'notify'])->name('front.flutter.notify');

    // CHECKOUT SECTION ENDS

    // Legacy routes - redirect to merchant cart
    Route::get('/payment/checkout', function() {
        return redirect()->route('merchant-cart.index')->with('info', __('Please proceed with checkout from the cart page'));
    })->name('payment.checkout');

    Route::get('/checkout/payment/{slug1}/{slug2}', function() {
        return redirect()->route('merchant-cart.index')->with('info', __('Please proceed with checkout from the cart page'));
    })->name('front.load.payment');

    Route::get('/payment/successfull/{get}', 'Front\FrontendController@success')->name('front.payment.success');

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

    Route::get('/merchant/subscription/check', 'Front\FrontendController@subcheck');

    // CRONJOB ENDS

    Route::post('the/muaadh/ocean/2441139', 'Front\FrontendController@subscription');
    Route::get('finalize', 'Front\FrontendController@finalize');
    Route::get('update-finalize', 'Front\FrontendController@updateFinalize');

    // MERCHANT AND PAGE SECTION
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

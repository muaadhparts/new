<?php

// ************************************ OPERATOR SECTION **********************************************

use App\Domain\Catalog\Models\Token;
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
    Route::get('/part', [App\Http\Controllers\SearchApiController::class, 'searchPart'])->name('api.search.part');
    Route::get('/vin', [App\Http\Controllers\SearchApiController::class, 'searchVin'])->name('api.search.vin');
    Route::post('/vin/select', [App\Http\Controllers\SearchApiController::class, 'selectVin'])->name('api.search.vin.select');
});

// Vehicle Search API Routes (AJAX-based)
Route::prefix('api/vehicle')->group(function () {
    Route::get('/suggestions', [App\Http\Controllers\VehicleSearchApiController::class, 'searchSuggestions'])->name('api.vehicle.suggestions');
    Route::get('/search', [App\Http\Controllers\VehicleSearchApiController::class, 'search'])->name('api.vehicle.search');
});


// Quick checkout - redirect to cart
Route::get('/checkout/quick', function() {
    return redirect()->route('merchant-cart.index');
})->name('front.checkout.quick');

Route::prefix('modal')->name('modal.')->group(function () {
    Route::get('/catalog-item/id/{catalogItem}',   [App\Http\Controllers\CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.id');
    Route::get('/catalog-item/part_number/{part_number}',      [App\Http\Controllers\CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item.part_number');
    Route::get('/alternative/{key}',      [App\Http\Controllers\CatalogItemDetailsController::class, 'alternativeFragment'])->name('alternative');
    Route::get('/quickview/{id}',         [App\Http\Controllers\CatalogItemDetailsController::class, 'quickFragment'])->name('quickview');
    Route::get('/offers/{catalogItemId}', [App\Http\Controllers\CatalogItemDetailsController::class, 'offersFragment'])->name('offers');
    Route::get('/offers-by-part/{part_number}', [App\Http\Controllers\CatalogItemDetailsController::class, 'offersByPartNumber'])->name('offers-by-part');
    Route::get('/catalog-item/{key}',          [App\Http\Controllers\CatalogItemDetailsController::class, 'catalogItemFragment'])->name('catalog-item');
    Route::get('/fitment/{catalogItemId}', [\App\Http\Controllers\Api\CatalogItemApiController::class, 'getFitmentDetails'])->name('fitment');
});

// ✅ API Routes with Rate Limiting
Route::prefix('api')->middleware(['web', 'throttle:120,1'])->group(function () {
    Route::get('/callouts', [App\Http\Controllers\CalloutController::class, 'show'])->name('api.callouts.show');
    Route::get('/callouts/html', [App\Http\Controllers\CalloutController::class, 'showHtml'])->name('api.callouts.html');
    Route::get('/callouts/metadata', [App\Http\Controllers\CalloutController::class, 'metadata'])->name('api.callouts.metadata');
    Route::get('/modal/alternative/{part_number}', [App\Http\Controllers\Api\CatalogItemApiController::class, 'getAlternatives'])->name('modal.alternative');
});





Route::get('/under-maintenance', [App\Http\Controllers\Front\FrontendController::class, 'maintenance'])->name('front-maintenance');

Route::prefix('operator')->group(function () {

    //------------ OPERATOR LOGIN SECTION ------------

    Route::get('/login', [App\Http\Controllers\Auth\Operator\LoginController::class, 'showForm'])->name('operator.login');
    Route::post('/login', [App\Http\Controllers\Auth\Operator\LoginController::class, 'login'])->name('operator.login.submit');
    Route::get('/logout', [App\Http\Controllers\Auth\Operator\LoginController::class, 'logout'])->name('operator.logout');

    //------------ OPERATOR LOGIN SECTION ENDS ------------

    //------------ OPERATOR FORGOT SECTION ------------

    Route::get('/forgot', [App\Http\Controllers\Auth\Operator\ForgotController::class, 'showForm'])->name('operator.forgot');
    Route::post('/forgot', [App\Http\Controllers\Auth\Operator\ForgotController::class, 'forgot'])->name('operator.forgot.submit');
    Route::get('/change-password/{token}', [App\Http\Controllers\Auth\Operator\ForgotController::class, 'showChangePassForm'])->name('operator.change.token');
    Route::post('/change-password', [App\Http\Controllers\Auth\Operator\ForgotController::class, 'changepass'])->name('operator.change.password');

    //------------ OPERATOR FORGOT SECTION ENDS ------------

    //------------ OPERATOR UNLOCK/PROTECTION SECTION ------------
    Route::get('/unlock', [App\Http\Controllers\Operator\UnlockController::class, 'show'])->name('operator.unlock');
    Route::post('/unlock', [App\Http\Controllers\Operator\UnlockController::class, 'verify'])->name('operator.unlock.verify');
    Route::get('/lock', [App\Http\Controllers\Operator\UnlockController::class, 'lock'])->name('operator.lock')->middleware('auth:operator');
    //------------ OPERATOR UNLOCK SECTION ENDS ------------

    //------------ PROTECTED OPERATOR ROUTES (Require Authentication + Protection) ------------
    Route::middleware(['auth:operator', 'operator.protection'])->group(function () {

        //------------ OPERATOR CATALOG EVENT SECTION ------------
        Route::get('/all/event/count', [App\Http\Controllers\Operator\CatalogEventController::class, 'allEventCount'])->name('all-event-count');
        Route::get('/user/event/show', [App\Http\Controllers\Operator\CatalogEventController::class, 'showUserEvents'])->name('user-event-show');
        Route::get('/user/event/clear', [App\Http\Controllers\Operator\CatalogEventController::class, 'clearUserEvents'])->name('user-event-clear');
        Route::get('/purchase/event/show', [App\Http\Controllers\Operator\CatalogEventController::class, 'showPurchaseEvents'])->name('purchase-event-show');
        Route::get('/purchase/event/clear', [App\Http\Controllers\Operator\CatalogEventController::class, 'clearPurchaseEvents'])->name('purchase-event-clear');
        Route::get('/catalog-item/event/show', [App\Http\Controllers\Operator\CatalogEventController::class, 'showCatalogItemEvents'])->name('catalog-item-event-show');
        Route::get('/catalog-item/event/clear', [App\Http\Controllers\Operator\CatalogEventController::class, 'clearCatalogItemEvents'])->name('catalog-item-event-clear');
        Route::get('/conv/event/show', [App\Http\Controllers\Operator\CatalogEventController::class, 'showConversationEvents'])->name('conv-event-show');
        Route::get('/conv/event/clear', [App\Http\Controllers\Operator\CatalogEventController::class, 'clearConversationEvents'])->name('conv-event-clear');
        //------------ OPERATOR CATALOG EVENT SECTION ENDS ------------

        //------------ OPERATOR DASHBOARD & PROFILE SECTION ------------
        Route::get('/', [App\Http\Controllers\Operator\DashboardController::class, 'index'])->name('operator.dashboard');
        Route::get('/profile', [App\Http\Controllers\Operator\DashboardController::class, 'profile'])->name('operator.profile');
        Route::post('/profile/update', [App\Http\Controllers\Operator\DashboardController::class, 'profileupdate'])->name('operator.profile.update');
        Route::get('/password', [App\Http\Controllers\Operator\DashboardController::class, 'passwordreset'])->name('operator.password');
        Route::post('/password/update', [App\Http\Controllers\Operator\DashboardController::class, 'changepass'])->name('operator.password.update');
        //------------ OPERATOR DASHBOARD & PROFILE SECTION ENDS ------------

        //------------ OPERATORPERFORMANCE MONITORING SECTION ------------
        Route::get('/performance', [App\Http\Controllers\Operator\PerformanceController::class, 'index'])->name('operator-performance');
        Route::get('/performance/slow-queries', [App\Http\Controllers\Operator\PerformanceController::class, 'slowQueries'])->name('operator-performance-slow-queries');
        Route::get('/performance/slow-requests', [App\Http\Controllers\Operator\PerformanceController::class, 'slowRequests'])->name('operator-performance-slow-requests');
        Route::get('/performance/repeated-queries', [App\Http\Controllers\Operator\PerformanceController::class, 'repeatedQueries'])->name('operator-performance-repeated-queries');
        Route::get('/performance/report', [App\Http\Controllers\Operator\PerformanceController::class, 'downloadReport'])->name('operator-performance-report');
        Route::get('/performance/api/summary', [App\Http\Controllers\Operator\PerformanceController::class, 'apiSummary'])->name('operator-performance-api-summary');
        Route::post('/performance/prune', [App\Http\Controllers\Operator\PerformanceController::class, 'pruneOldEntries'])->name('operator-performance-prune');
        //------------ OPERATORPERFORMANCE MONITORING SECTION ENDS ------------

        //------------ OPERATORAPI CREDENTIALS SECTION ------------
        Route::get('/credentials', [App\Http\Controllers\Operator\ApiCredentialController::class, 'index'])->name('operator.credentials.index');
        Route::get('/credentials/create', [App\Http\Controllers\Operator\ApiCredentialController::class, 'create'])->name('operator.credentials.create');
        Route::post('/credentials', [App\Http\Controllers\Operator\ApiCredentialController::class, 'store'])->name('operator.credentials.store');
        Route::get('/credentials/{id}/edit', [App\Http\Controllers\Operator\ApiCredentialController::class, 'edit'])->name('operator.credentials.edit');
        Route::put('/credentials/{id}', [App\Http\Controllers\Operator\ApiCredentialController::class, 'update'])->name('operator.credentials.update');
        Route::delete('/credentials/{id}', [App\Http\Controllers\Operator\ApiCredentialController::class, 'destroy'])->name('operator.credentials.destroy');
        Route::post('/credentials/{id}/toggle', [App\Http\Controllers\Operator\ApiCredentialController::class, 'toggle'])->name('operator.credentials.toggle');
        Route::post('/credentials/{id}/test', [App\Http\Controllers\Operator\ApiCredentialController::class, 'test'])->name('operator.credentials.test');
        //------------ OPERATORAPI CREDENTIALS SECTION ENDS ------------

        //------------ OPERATORMERCHANT CREDENTIALS SECTION ------------
        Route::get('/merchant-credentials', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'index'])->name('operator.merchant-credentials.index');
        Route::get('/merchant-credentials/create', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'create'])->name('operator.merchant-credentials.create');
        Route::post('/merchant-credentials', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'store'])->name('operator.merchant-credentials.store');
        Route::get('/merchant-credentials/{id}/edit', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'edit'])->name('operator.merchant-credentials.edit');
        Route::put('/merchant-credentials/{id}', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'update'])->name('operator.merchant-credentials.update');
        Route::delete('/merchant-credentials/{id}', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'destroy'])->name('operator.merchant-credentials.destroy');
        Route::post('/merchant-credentials/{id}/toggle', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'toggle'])->name('operator.merchant-credentials.toggle');
        Route::post('/merchant-credentials/{id}/test', [App\Http\Controllers\Operator\MerchantCredentialController::class, 'test'])->name('operator.merchant-credentials.test');
        //------------ OPERATORMERCHANT CREDENTIALS SECTION ENDS ------------
    });

    //------------ OPERATORPURCHASE SECTION ------------

    Route::group(['middleware' => 'permissions:purchases'], function () {

        Route::get('/purchases/datatables/{slug}', [App\Http\Controllers\Operator\PurchaseController::class, 'datatables'])->name('operator-purchase-datatables'); //JSON REQUEST
        Route::get('/purchases', [App\Http\Controllers\Operator\PurchaseController::class, 'purchases'])->name('operator-purchases-all');
        Route::get('/purchase/edit/{id}', [App\Http\Controllers\Operator\PurchaseController::class, 'edit'])->name('operator-purchase-edit');
        Route::post('/purchase/update/{id}', [App\Http\Controllers\Operator\PurchaseController::class, 'update'])->name('operator-purchase-update');
        Route::get('/purchase/{id}/show', [App\Http\Controllers\Operator\PurchaseController::class, 'show'])->name('operator-purchase-show');
        Route::get('/purchase/{id}/invoice', [App\Http\Controllers\Operator\PurchaseController::class, 'invoice'])->name('operator-purchase-invoice');
        Route::get('/purchase/{id}/print', [App\Http\Controllers\Operator\PurchaseController::class, 'printpage'])->name('operator-purchase-print');
        Route::get('/purchase/{id1}/status/{status}', [App\Http\Controllers\Operator\PurchaseController::class, 'status'])->name('operator-purchase-status');
        Route::post('/purchase/email/', [App\Http\Controllers\Operator\PurchaseController::class, 'emailsub'])->name('operator-purchase-emailsub');
        Route::get('/send-message', [App\Http\Controllers\Operator\PurchaseController::class, 'emailsub'])->name('operator-send-message'); // Alias for email modal in user/courier lists
        Route::post('/purchase/catalogItem-submit', [App\Http\Controllers\Operator\PurchaseController::class, 'catalogItem_submit'])->name('operator-purchase-catalogItem-submit');
        Route::get('/purchase/catalogItem-show/{id}', [App\Http\Controllers\Operator\PurchaseController::class, 'catalogItem_show']);
        // REMOVED: addcart, updatecart - MerchantCart class deleted
        Route::get('/purchasecart/catalogItem-edit/{id}/{itemid}/{purchaseid}', [App\Http\Controllers\Operator\PurchaseController::class, 'catalogItem_edit'])->name('operator-purchase-catalogItem-edit');
        Route::get('/purchasecart/catalogItem-delete/{id}/{purchaseid}', [App\Http\Controllers\Operator\PurchaseController::class, 'catalogItem_delete'])->name('operator-purchase-catalogItem-delete');
        // Purchase Tracking

        // CREATE PURCHASE

        Route::get('/purchase/catalog-item/datatables', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'datatables'])->name('operator-purchase-catalog-item-datatables');
        Route::get('/purchase/create', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'create'])->name('operator-purchase-create');
        Route::get('/purchase/catalog-item/add/{catalog_item_id}', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'addCatalogItem'])->name('operator-purchase-catalog-item-add');
        Route::get('/purchase/catalog-item/add', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'purchaseStore'])->name('operator.purchase.store.new');
        Route::get('/purchase/catalog-item/remove/{catalog_item_id}', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'removePurchaseCatalogItem'])->name('operator.purchase.catalog-item.remove');
        Route::get('/purchase/remove-cart/{catalog_item_id}', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'removePurchaseCatalogItem'])->name('operator.purchase.remove.cart');
        Route::get('/purchase/create/catalog-item-show/{id}', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'catalog_item_show']);
        // REMOVED: addcart, removecart, CreatePurchaseSubmit - MerchantCart class deleted
        Route::get('/purchase/create/user-address', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'userAddress']);
        Route::post('/purchase/create/user-address', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'userAddressSubmit'])->name('operator.purchase.create.user.address');
        Route::post('/purchase/create/purchase/view', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'viewCreatePurchase'])->name('operator.purchase.create.view');
        Route::get('/purchase/create/submit', [App\Http\Controllers\Operator\PurchaseCreateController::class, 'submitPurchase'])->name('operator-purchase-create-submit');

        Route::get('/purchase/{id}/timeline', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'index'])->name('operator-purchase-timeline');
        Route::get('/purchase/{id}/timelineload', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'load'])->name('operator-purchase-timeline-load');
        Route::post('/purchase/timeline/store', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'store'])->name('operator-purchase-timeline-store');
        Route::get('/purchase/timeline/add', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'add'])->name('operator-purchase-timeline-add');
        Route::get('/purchase/timeline/edit/{id}', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'edit'])->name('operator-purchase-timeline-edit');
        Route::post('/purchase/timeline/update/{id}', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'update'])->name('operator-purchase-timeline-update');
        Route::delete('/purchase/timeline/delete/{id}', [App\Http\Controllers\Operator\PurchaseTimelineController::class, 'delete'])->name('operator-purchase-timeline-delete');

        // Purchase Tracking Ends

    });

    //------------ OPERATORPURCHASE SECTION ENDS------------

    //------------ OPERATORSHIPMENTS SECTION ------------

    Route::group(['middleware' => 'permissions:purchases'], function () {
        Route::get('/shipments', [App\Http\Controllers\Operator\ShipmentController::class, 'index'])->name('operator.shipments.index');
        Route::get('/shipments/show/{tracking}', [App\Http\Controllers\Operator\ShipmentController::class, 'show'])->name('operator.shipments.show');
        Route::get('/shipments/refresh/{tracking}', [App\Http\Controllers\Operator\ShipmentController::class, 'refresh'])->name('operator.shipments.refresh');
        Route::post('/shipments/cancel/{tracking}', [App\Http\Controllers\Operator\ShipmentController::class, 'cancel'])->name('operator.shipments.cancel');
        Route::get('/shipments/export', [App\Http\Controllers\Operator\ShipmentController::class, 'export'])->name('operator.shipments.export');
        Route::post('/shipments/bulk-refresh', [App\Http\Controllers\Operator\ShipmentController::class, 'bulkRefresh'])->name('operator.shipments.bulk-refresh');
        Route::get('/shipments/reports', [App\Http\Controllers\Operator\ShipmentController::class, 'reports'])->name('operator.shipments.reports');
    });

    //------------ OPERATORSHIPMENTS SECTION ENDS------------

    /////////////////////////////// ////////////////////////////////////////////

    // --------------- ADMIN COUNTRY & CITY SECTION (Protected) ---------------//
    Route::middleware(['auth:operator'])->group(function () {
        Route::get('/country/datatables', [App\Http\Controllers\Operator\CountryController::class, 'datatables'])->name('operator-country-datatables');
        Route::get('/manage/country', [App\Http\Controllers\Operator\CountryController::class, 'manageCountry'])->name('operator-country-index');
        Route::get('/manage/country/status/{id1}/{id2}', [App\Http\Controllers\Operator\CountryController::class, 'status'])->name('operator-country-status');
        Route::get('/country/delete/{id}', [App\Http\Controllers\Operator\CountryController::class, 'delete'])->name('operator-country-delete');
        Route::get('/country/tax/datatables', [App\Http\Controllers\Operator\CountryController::class, 'taxDatatables'])->name('operator-country-tax-datatables');
        Route::get('/manage/country/tax', [App\Http\Controllers\Operator\CountryController::class, 'country_tax'])->name('operator-country-tax');
        Route::get('/country/set-tax/{id}', [App\Http\Controllers\Operator\CountryController::class, 'setTax'])->name('operator-set-tax');
        Route::post('/country/set-tax/store/{id}', [App\Http\Controllers\Operator\CountryController::class, 'updateTax'])->name('operator-tax-update');

        Route::get('/city/datatables/{country}', [App\Http\Controllers\Operator\CityController::class, 'datatables'])->name('operator-city-datatables');
        Route::get('/manage/city/{country}', [App\Http\Controllers\Operator\CityController::class, 'managecity'])->name('operator-city-index');
        Route::get('/city/create/{country}', [App\Http\Controllers\Operator\CityController::class, 'create'])->name('operator-city-create');
        Route::post('/city/store/{country}', [App\Http\Controllers\Operator\CityController::class, 'store'])->name('operator-city-store');
        Route::get('/city/status/{id1}/{id2}', [App\Http\Controllers\Operator\CityController::class, 'status'])->name('operator-city-status');
        Route::get('/city/edit/{id}', [App\Http\Controllers\Operator\CityController::class, 'edit'])->name('operator-city-edit');
        Route::post('/city/update/{id}', [App\Http\Controllers\Operator\CityController::class, 'update'])->name('operator-city-update');
        Route::delete('/city/delete/{id}', [App\Http\Controllers\Operator\CityController::class, 'delete'])->name('operator-city-delete');
    });
    // --------------- ADMIN COUNTRY & CITY SECTION ENDS ---------------//

    //------------ OPERATORCATEGORY SECTION ENDS------------

    Route::group(['middleware' => 'permissions:earning'], function () {

        // -------------------------- Admin Total Income Route --------------------------//
        Route::get('tax/calculate', [App\Http\Controllers\Operator\IncomeController::class, 'taxCalculate'])->name('operator-tax-calculate-income');
        Route::get('withdraw/earning', [App\Http\Controllers\Operator\IncomeController::class, 'withdrawIncome'])->name('operator-withdraw-income');
        Route::get('commission/earning', [App\Http\Controllers\Operator\IncomeController::class, 'commissionIncome'])->name('operator-commission-income');
        Route::get('merchant/report', [App\Http\Controllers\Operator\IncomeController::class, 'merchantReport'])->name('operator-merchant-report');
        Route::get('commission/detailed', [App\Http\Controllers\Operator\IncomeController::class, 'commissionIncomeDetailed'])->name('operator-commission-detailed');
        // -------------------------- Admin Total Income Route --------------------------//
    });

    // ========================== ACCOUNTING LEDGER SYSTEM ==========================//
    Route::group(['prefix' => 'accounts'], function () {
        // Dashboard
        Route::get('/', [App\Http\Controllers\Operator\AccountLedgerController::class, 'index'])->name('operator.accounts.index');

        // Parties Lists
        Route::get('/merchants', [App\Http\Controllers\Operator\AccountLedgerController::class, 'merchants'])->name('operator.accounts.merchants');
        Route::get('/couriers', [App\Http\Controllers\Operator\AccountLedgerController::class, 'couriers'])->name('operator.accounts.couriers');
        Route::get('/shipping', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingProviders'])->name('operator.accounts.shipping');
        Route::get('/payment', [App\Http\Controllers\Operator\AccountLedgerController::class, 'paymentProviders'])->name('operator.accounts.payment');

        // Party Statement
        Route::get('/party/{party}/statement', [App\Http\Controllers\Operator\AccountLedgerController::class, 'partyStatement'])->name('operator.accounts.party.statement');
        Route::get('/transaction/{transaction}', [App\Http\Controllers\Operator\AccountLedgerController::class, 'transactionDetails'])->name('operator.accounts.transaction');

        // Settlements
        Route::get('/settlements', [App\Http\Controllers\Operator\AccountLedgerController::class, 'settlements'])->name('operator.accounts.settlements');
        Route::get('/settlements/create', [App\Http\Controllers\Operator\AccountLedgerController::class, 'createSettlementForm'])->name('operator.accounts.settlements.create');
        Route::post('/settlements', [App\Http\Controllers\Operator\AccountLedgerController::class, 'storeSettlement'])->name('operator.accounts.settlements.store');
        Route::get('/settlements/{batch}', [App\Http\Controllers\Operator\AccountLedgerController::class, 'settlementDetails'])->name('operator.accounts.settlements.show');

        // Courier Settlements - تسويات المناديب
        Route::post('/settlements/courier', [App\Http\Controllers\Operator\AccountLedgerController::class, 'courierSettlement'])->name('operator.accounts.settlements.courier');
        Route::get('/settlements/courier/{courierId}/pending', [App\Http\Controllers\Operator\AccountLedgerController::class, 'pendingSettlementsByCourier'])->name('operator.accounts.settlements.courier.pending');

        // Shipping Company Settlements - تسويات شركات الشحن
        Route::post('/settlements/shipping', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingCompanySettlement'])->name('operator.accounts.settlements.shipping');
        Route::get('/settlements/shipping/{providerCode}/pending', [App\Http\Controllers\Operator\AccountLedgerController::class, 'pendingSettlementsByProvider'])->name('operator.accounts.settlements.shipping.pending');

        // Sync Parties
        Route::post('/sync-parties', [App\Http\Controllers\Operator\AccountLedgerController::class, 'syncParties'])->name('operator.accounts.sync');

        // Reports
        Route::get('/reports/receivables', [App\Http\Controllers\Operator\AccountLedgerController::class, 'receivablesReport'])->name('operator.accounts.reports.receivables');
        Route::get('/reports/payables', [App\Http\Controllers\Operator\AccountLedgerController::class, 'payablesReport'])->name('operator.accounts.reports.payables');
        Route::get('/reports/shipping', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingReport'])->name('operator.accounts.reports.shipping');
        Route::get('/reports/payment', [App\Http\Controllers\Operator\AccountLedgerController::class, 'paymentReport'])->name('operator.accounts.reports.payment');
        Route::get('/reports/tax', [App\Http\Controllers\Operator\AccountLedgerController::class, 'taxReport'])->name('operator.accounts.reports.tax');

        // التقارير المحسنة (من Ledger فقط)
        Route::get('/reports/platform', [App\Http\Controllers\Operator\AccountLedgerController::class, 'platformReport'])->name('operator.accounts.reports.platform');
        Route::get('/reports/merchants-summary', [App\Http\Controllers\Operator\AccountLedgerController::class, 'merchantsSummary'])->name('operator.accounts.reports.merchants-summary');
        Route::get('/reports/couriers', [App\Http\Controllers\Operator\AccountLedgerController::class, 'couriersReport'])->name('operator.accounts.reports.couriers');
        Route::get('/reports/shipping-companies', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingCompaniesReport'])->name('operator.accounts.reports.shipping-companies');
        Route::get('/reports/receivables-payables', [App\Http\Controllers\Operator\AccountLedgerController::class, 'receivablesPayablesReport'])->name('operator.accounts.reports.receivables-payables');

        // كشف حساب التاجر
        Route::get('/merchant-statement/{merchantId}', [App\Http\Controllers\Operator\AccountLedgerController::class, 'merchantStatement'])->name('operator.accounts.merchant-statement');

        // شركات الشحن - كشف حساب مفصل
        Route::get('/shipping-companies', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingCompanyList'])->name('operator.accounts.shipping-companies');
        Route::get('/shipping-company/{providerCode}/statement', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingCompanyStatement'])->name('operator.accounts.shipping-company.statement');
        Route::get('/shipping-company/{providerCode}/statement/pdf', [App\Http\Controllers\Operator\AccountLedgerController::class, 'shippingCompanyStatementPdf'])->name('operator.accounts.shipping-company.statement.pdf');
    });
    // ========================== END ACCOUNTING LEDGER SYSTEM ==========================//

    /////////////////////////////// ////////////////////////////////////////////

    // Note: Old Category/Subcategory/Childcategory and Attribute routes removed - now using TreeCategories

    //------------ OPERATORCATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:catalog_items'], function () {
        Route::get('/catalog-items/datatables', [App\Http\Controllers\Operator\CatalogItemController::class, 'datatables'])->name('operator-catalog-item-datatables');
        Route::get('/catalog-items', [App\Http\Controllers\Operator\CatalogItemController::class, 'index'])->name('operator-catalog-item-index');

        // CREATE SECTION
        Route::get('/catalog-items/{slug}/create', [App\Http\Controllers\Operator\CatalogItemController::class, 'create'])->name('operator-catalog-item-create');
        Route::post('/catalog-items/store', [App\Http\Controllers\Operator\CatalogItemController::class, 'store'])->name('operator-catalog-item-store');

        // EDIT SECTION
        Route::get('/catalog-items/edit/{catalogItemId}', [App\Http\Controllers\Operator\CatalogItemController::class, 'edit'])->name('operator-catalog-item-edit');
        Route::post('/catalog-items/edit/{catalogItemId}', [App\Http\Controllers\Operator\CatalogItemController::class, 'update'])->name('operator-catalog-item-update');

        // DELETE SECTION
        Route::delete('/catalog-items/delete/{id}', [App\Http\Controllers\Operator\CatalogItemController::class, 'destroy'])->name('operator-catalog-item-delete');

        // SETTINGS
        Route::get('/catalog-items/settings', [App\Http\Controllers\Operator\CatalogItemController::class, 'catalogItemSettings'])->name('operator-gs-catalog-item-settings');
        Route::post('/catalog-items/settings/update', [App\Http\Controllers\Operator\CatalogItemController::class, 'settingUpdate'])->name('operator-gs-catalog-item-settings-update');

        // CATALOG ITEM IMAGES SECTION
        Route::get('/catalog-items/images', [App\Http\Controllers\Operator\CatalogItemImageController::class, 'index'])->name('operator-catalog-item-images');
        Route::get('/catalog-items/images/autocomplete', [App\Http\Controllers\Operator\CatalogItemImageController::class, 'autocomplete'])->name('operator-catalog-item-images-autocomplete');
        Route::get('/catalog-items/images/{id}', [App\Http\Controllers\Operator\CatalogItemImageController::class, 'show'])->name('operator-catalog-item-images-show');
        Route::post('/catalog-items/images/{id}', [App\Http\Controllers\Operator\CatalogItemImageController::class, 'update'])->name('operator-catalog-item-images-update');

        // MERCHANT ITEM IMAGES SECTION
        Route::get('/merchant-items/images', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'index'])->name('operator-merchant-item-images');
        Route::get('/merchant-items/images/autocomplete', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'autocomplete'])->name('operator-merchant-item-images-autocomplete');
        Route::get('/merchant-items/images/merchants', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'getMerchants'])->name('operator-merchant-item-images-merchants');
        Route::get('/merchant-items/images/branches', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'getBranches'])->name('operator-merchant-item-images-branches');
        Route::get('/merchant-items/images/quality-brands', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'getQualityBrands'])->name('operator-merchant-item-images-quality-brands');
        Route::get('/merchant-items/images/photos/{merchant_item_id}', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'getPhotos'])->name('operator-merchant-item-images-photos');
        Route::post('/merchant-items/images/store', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'store'])->name('operator-merchant-item-images-store');
        Route::delete('/merchant-items/images/{id}', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'destroy'])->name('operator-merchant-item-images-delete');
        Route::post('/merchant-items/images/order', [App\Http\Controllers\Operator\MerchantItemImageController::class, 'updateOrder'])->name('operator-merchant-item-images-order');
    });

    //------------ OPERATORCATALOG ITEM SECTION ENDS------------


    //------------ OPERATORCATALOGITEM DISCUSSION SECTION ------------

    Route::group(['middleware' => 'permissions:catalogItem_discussion'], function () {

        // CATALOG REVIEW SECTION ------------

        Route::get('/catalog-reviews/datatables', [App\Http\Controllers\Operator\CatalogReviewController::class, 'datatables'])->name('operator-catalog-review-datatables'); //JSON REQUEST
        Route::get('/catalog-reviews', [App\Http\Controllers\Operator\CatalogReviewController::class, 'index'])->name('operator-catalog-review-index');
        Route::delete('/catalog-reviews/delete/{id}', [App\Http\Controllers\Operator\CatalogReviewController::class, 'destroy'])->name('operator-catalog-review-delete');
        Route::get('/catalog-reviews/show/{id}', [App\Http\Controllers\Operator\CatalogReviewController::class, 'show'])->name('operator-catalog-review-show');

        // CATALOG REVIEW SECTION ENDS------------

        // BUYER NOTE SECTION ------------

        Route::get('/buyer-notes/datatables', [App\Http\Controllers\Operator\BuyerNoteController::class, 'datatables'])->name('operator-buyer-note-datatables'); //JSON REQUEST
        Route::get('/buyer-notes', [App\Http\Controllers\Operator\BuyerNoteController::class, 'index'])->name('operator-buyer-note-index');
        Route::delete('/buyer-notes/delete/{id}', [App\Http\Controllers\Operator\BuyerNoteController::class, 'destroy'])->name('operator-buyer-note-delete');
        Route::get('/buyer-notes/show/{id}', [App\Http\Controllers\Operator\BuyerNoteController::class, 'show'])->name('operator-buyer-note-show');

        // BUYER NOTE SECTION ENDS ------------

        // ABUSE FLAG SECTION ------------

        Route::get('/abuse-flags/datatables', [App\Http\Controllers\Operator\AbuseFlagController::class, 'datatables'])->name('operator-abuse-flag-datatables'); //JSON REQUEST
        Route::get('/abuse-flags', [App\Http\Controllers\Operator\AbuseFlagController::class, 'index'])->name('operator-abuse-flag-index');
        Route::delete('/abuse-flags/delete/{id}', [App\Http\Controllers\Operator\AbuseFlagController::class, 'destroy'])->name('operator-abuse-flag-delete');
        Route::get('/abuse-flags/show/{id}', [App\Http\Controllers\Operator\AbuseFlagController::class, 'show'])->name('operator-abuse-flag-show');

        // ABUSE FLAG SECTION ENDS ------------

    });

    //------------ OPERATORPRODUCT DISCUSSION SECTION ENDS ------------

    //------------ OPERATORUSER SECTION ------------

    Route::group(['middleware' => 'permissions:customers'], function () {

        Route::get('/users/datatables', [App\Http\Controllers\Operator\UserController::class, 'datatables'])->name('operator-user-datatables'); //JSON REQUEST
        Route::get('/users', [App\Http\Controllers\Operator\UserController::class, 'index'])->name('operator-user-index');
        Route::get('/users/create', [App\Http\Controllers\Operator\UserController::class, 'create'])->name('operator-user-create');
        Route::post('/users/store', [App\Http\Controllers\Operator\UserController::class, 'store'])->name('operator-user-store');
        Route::get('/users/edit/{id}', [App\Http\Controllers\Operator\UserController::class, 'edit'])->name('operator-user-edit');
        Route::post('/users/edit/{id}', [App\Http\Controllers\Operator\UserController::class, 'update'])->name('operator-user-update');
        Route::delete('/users/delete/{id}', [App\Http\Controllers\Operator\UserController::class, 'destroy'])->name('operator-user-delete');
        Route::get('/user/{id}/show', [App\Http\Controllers\Operator\UserController::class, 'show'])->name('operator-user-show');
        Route::get('/users/ban/{id1}/{id2}', [App\Http\Controllers\Operator\UserController::class, 'ban'])->name('operator-user-ban');
        Route::get('/user/default/image', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'user_image'])->name('operator-user-image');
        Route::get('/users/merchant/{id}', [App\Http\Controllers\Operator\UserController::class, 'merchant'])->name('operator-user-merchant');
        Route::post('/user/merchant/{id}', [App\Http\Controllers\Operator\UserController::class, 'setMerchant'])->name('operator-user-merchant-update');

        //USER WITHDRAW SECTION

        Route::get('/users/withdraws/datatables', [App\Http\Controllers\Operator\UserController::class, 'withdrawdatatables'])->name('operator-withdraw-datatables'); //JSON REQUEST
        Route::get('/users/withdraws', [App\Http\Controllers\Operator\UserController::class, 'withdraws'])->name('operator-withdraw-index');
        Route::get('/user/withdraw/{id}/show', [App\Http\Controllers\Operator\UserController::class, 'withdrawdetails'])->name('operator-withdraw-show');
        Route::get('/users/withdraws/accept/{id}', [App\Http\Controllers\Operator\UserController::class, 'accept'])->name('operator-withdraw-accept');
        Route::get('/user/withdraws/reject/{id}', [App\Http\Controllers\Operator\UserController::class, 'reject'])->name('operator-withdraw-reject');

        // WITHDRAW SECTION ENDS

        //COURIER WITHDRAW SECTION

        Route::get('/courier/withdraws/datatables', [App\Http\Controllers\Operator\CourierController::class, 'withdrawdatatables'])->name('operator-withdraw-courier-datatables'); //JSON REQUEST
        Route::get('/courier/withdraws', [App\Http\Controllers\Operator\CourierController::class, 'withdraws'])->name('operator-withdraw-courier-index');
        Route::get('/courier/withdraw/show/{id}', [App\Http\Controllers\Operator\CourierController::class, 'withdrawdetails'])->name('operator-withdraw-courier-show');
        Route::get('/courier/withdraw/accept/{id}', [App\Http\Controllers\Operator\CourierController::class, 'accept'])->name('operator-withdraw-courier-accept');
        Route::get('/courier/withdraw/reject/{id}', [App\Http\Controllers\Operator\CourierController::class, 'reject'])->name('operator-withdraw-courier-reject');

        // WITHDRAW SECTION ENDS

    });

    Route::group(['middleware' => 'permissions:couriers'], function () {

        Route::get('/couriers/datatables', [App\Http\Controllers\Operator\CourierController::class, 'datatables'])->name('operator-courier-datatables'); //JSON REQUEST
        Route::get('/couriers', [App\Http\Controllers\Operator\CourierController::class, 'index'])->name('operator-courier-index');

        Route::delete('/couriers/delete/{id}', [App\Http\Controllers\Operator\CourierController::class, 'destroy'])->name('operator-courier-delete');
        Route::get('/courier/{id}/show', [App\Http\Controllers\Operator\CourierController::class, 'show'])->name('operator-courier-show');
        Route::get('/couriers/ban/{id1}/{id2}', [App\Http\Controllers\Operator\CourierController::class, 'ban'])->name('operator-courier-ban');
        Route::get('/courier/default/image', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'courier_image'])->name('operator-courier-image');

        // WITHDRAW SECTION

        Route::get('/couriers/withdraws/datatables', [App\Http\Controllers\Operator\CourierController::class, 'withdrawdatatables'])->name('operator-courier-withdraw-datatables'); //JSON REQUEST
        Route::get('/couriers/withdraws', [App\Http\Controllers\Operator\CourierController::class, 'withdraws'])->name('operator-courier-withdraw-index');
        Route::get('/courier/withdraw/{id}/show', [App\Http\Controllers\Operator\CourierController::class, 'withdrawdetails'])->name('operator-courier-withdraw-show');
        Route::get('/couriers/withdraws/accept/{id}', [App\Http\Controllers\Operator\CourierController::class, 'accept'])->name('operator-courier-withdraw-accept');
        Route::get('/courier/withdraws/reject/{id}', [App\Http\Controllers\Operator\CourierController::class, 'reject'])->name('operator-courier-withdraw-reject');

        // WITHDRAW SECTION ENDS

        // COURIER MANAGEMENT SECTION
        Route::get('/couriers/balances', [App\Http\Controllers\Operator\CourierManagementController::class, 'index'])->name('operator-courier-balances');
        Route::get('/courier/{id}/details', [App\Http\Controllers\Operator\CourierManagementController::class, 'show'])->name('operator-courier-details');
        Route::get('/courier/{id}/unsettled', [App\Http\Controllers\Operator\CourierManagementController::class, 'unsettledDeliveries'])->name('operator-courier-unsettled');
        Route::get('/couriers/settlements', [App\Http\Controllers\Operator\CourierManagementController::class, 'settlements'])->name('operator-courier-settlements');
        Route::post('/courier/{id}/create-settlement', [App\Http\Controllers\Operator\CourierManagementController::class, 'createSettlement'])->name('operator-courier-create-settlement');
        // Use /accounts/couriers for courier accounting (AccountLedgerController)
        // COURIER MANAGEMENT SECTION ENDS

    });

    //------------ OPERATORMERCHANT SECTION ------------

    Route::group(['middleware' => 'permissions:vendors'], function () {

        Route::get('/merchants/datatables', [App\Http\Controllers\Operator\MerchantController::class, 'datatables'])->name('operator-merchant-datatables');
        Route::get('/merchants', [App\Http\Controllers\Operator\MerchantController::class, 'index'])->name('operator-merchant-index');

        Route::get('/merchants/{id}/show', [App\Http\Controllers\Operator\MerchantController::class, 'show'])->name('operator-merchant-show');
        Route::get('/merchants/{id}/items/datatables', [App\Http\Controllers\Operator\MerchantController::class, 'merchantItemsDatatables'])->name('operator-merchant-items-datatables');
        Route::get('/merchants/secret/login/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'secretLogin'])->name('operator-merchant-secret');
        Route::get('/merchant/edit/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'edit'])->name('operator-merchant-edit');
        Route::post('/merchant/edit/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'update'])->name('operator-merchant-update');

        Route::get('/merchant/request-trust-badge/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'requestTrustBadge'])->name('operator-merchant-request-trust-badge');
        Route::post('/merchant/request-trust-badge/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'requestTrustBadgeSubmit'])->name('operator-merchant-request-trust-badge-submit');

        Route::get('/merchants/status/{id1}/{id2}', [App\Http\Controllers\Operator\MerchantController::class, 'status'])->name('operator-merchant-st');
        Route::delete('/merchants/delete/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'destroy'])->name('operator-merchant-delete');

        Route::get('/merchants/withdraws/datatables', [App\Http\Controllers\Operator\MerchantController::class, 'withdrawdatatables'])->name('operator-merchant-withdraw-datatables'); //JSON REQUEST
        Route::get('/merchants/withdraws', [App\Http\Controllers\Operator\MerchantController::class, 'withdraws'])->name('operator-merchant-withdraw-index');
        Route::get('/merchants/withdraw/{id}/show', [App\Http\Controllers\Operator\MerchantController::class, 'withdrawdetails'])->name('operator-merchant-withdraw-show');
        Route::get('/merchants/withdraws/accept/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'accept'])->name('operator-merchant-withdraw-accept');
        Route::get('/merchants/withdraws/reject/{id}', [App\Http\Controllers\Operator\MerchantController::class, 'reject'])->name('operator-merchant-withdraw-reject');
    });

    //------------ OPERATORMERCHANT SECTION ENDS ------------

    //------------ MERCHANT COMMISSION SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_membership_plans'], function () {
        Route::get('/merchant-commissions/datatables', [App\Http\Controllers\Operator\MerchantCommissionController::class, 'datatables'])->name('operator-merchant-commission-datatables');
        Route::get('/merchant-commissions', [App\Http\Controllers\Operator\MerchantCommissionController::class, 'index'])->name('operator-merchant-commission-index');
        Route::get('/merchant-commissions/edit/{id}', [App\Http\Controllers\Operator\MerchantCommissionController::class, 'edit'])->name('operator-merchant-commission-edit');
        Route::post('/merchant-commissions/update/{id}', [App\Http\Controllers\Operator\MerchantCommissionController::class, 'update'])->name('operator-merchant-commission-update');
        Route::post('/merchant-commissions/bulk-update', [App\Http\Controllers\Operator\MerchantCommissionController::class, 'bulkUpdate'])->name('operator-merchant-commission-bulk-update');
    });

    //------------ MERCHANT COMMISSION SECTION ENDS ------------

    //------------ MERCHANT TRUST BADGE SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_verifications'], function () {

        Route::get('/trust-badges/datatables/{status}', [App\Http\Controllers\Operator\TrustBadgeController::class, 'datatables'])->name('operator-trust-badge-datatables');
        Route::get('/trust-badges/{slug}', [App\Http\Controllers\Operator\TrustBadgeController::class, 'index'])->name('operator-trust-badge-index');
        Route::get('/trust-badges/show/attachment', [App\Http\Controllers\Operator\TrustBadgeController::class, 'show'])->name('operator-trust-badge-show');
        Route::get('/trust-badges/edit/{id}', [App\Http\Controllers\Operator\TrustBadgeController::class, 'edit'])->name('operator-trust-badge-edit');
        Route::post('/trust-badges/edit/{id}', [App\Http\Controllers\Operator\TrustBadgeController::class, 'update'])->name('operator-trust-badge-update');
        Route::get('/trust-badges/status/{id1}/{id2}', [App\Http\Controllers\Operator\TrustBadgeController::class, 'status'])->name('operator-trust-badge-status');
        Route::delete('/trust-badges/delete/{id}', [App\Http\Controllers\Operator\TrustBadgeController::class, 'destroy'])->name('operator-trust-badge-delete');
    });

    //------------ MERCHANT TRUST BADGE SECTION ENDS ------------

    //------------ OPERATORSUPPORT TICKET SECTION ------------

    Route::group(['middleware' => 'permissions:messages'], function () {

        Route::get('/support-tickets/datatables/{type}', [App\Http\Controllers\Operator\SupportTicketController::class, 'datatables'])->name('operator-support-ticket-datatables');
        Route::get('/tickets', [App\Http\Controllers\Operator\SupportTicketController::class, 'index'])->name('operator-support-ticket-index');
        Route::get('/disputes', [App\Http\Controllers\Operator\SupportTicketController::class, 'dispute'])->name('operator-support-ticket-dispute');
        Route::get('/support-ticket/{id}', [App\Http\Controllers\Operator\SupportTicketController::class, 'message'])->name('operator-support-ticket-show');
        Route::get('/support-ticket/load/{id}', [App\Http\Controllers\Operator\SupportTicketController::class, 'messageshow'])->name('operator-support-ticket-load');
        Route::post('/support-ticket/post', [App\Http\Controllers\Operator\SupportTicketController::class, 'postmessage'])->name('operator-support-ticket-store');
        Route::delete('/support-ticket/{id}/delete', [App\Http\Controllers\Operator\SupportTicketController::class, 'messagedelete'])->name('operator-support-ticket-delete');
        Route::post('/user/send/support-ticket/admin', [App\Http\Controllers\Operator\SupportTicketController::class, 'usercontact'])->name('operator-send-support-ticket');
    });

    //------------ OPERATORSUPPORT TICKET SECTION ENDS ------------

    // PUBLICATION SECTION REMOVED - Feature deleted

    //------------ OPERATORGENERAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:muaadh_settings'], function () {

        Route::get('/general-settings/logo', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'logo'])->name('operator-gs-logo');
        Route::get('/general-settings/favicon', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'favicon'])->name('operator-gs-fav');
        Route::get('/general-settings/loader', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'loader'])->name('operator-gs-load');
        Route::get('/general-settings/contents', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'websitecontent'])->name('operator-gs-contents');
        Route::get('/general-settings/theme-colors', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'themeColors'])->name('operator-theme-colors');
        Route::post('/general-settings/theme-colors/update', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'updateThemeColors'])->name('operator-theme-colors-update');
        Route::get('/general-settings/affilate', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'affilate'])->name('operator-gs-affilate');
        Route::get('/general-settings/error-banner', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'error_banner'])->name('operator-gs-error-banner');
        Route::get('/general-settings/popup', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'popup'])->name('operator-gs-popup');
        Route::get('/general-settings/footer', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'footer'])->name('operator-gs-footer');
        // Breadcrumb banner removed - using modern minimal design
        Route::get('/general-settings/maintenance', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'maintain'])->name('operator-gs-maintenance');

        // Deal Of The Day

        //------------ OPERATORSHIPPING ------------

        Route::get('/shipping/datatables', [App\Http\Controllers\Operator\ShippingController::class, 'datatables'])->name('operator-shipping-datatables');
        Route::get('/shipping', [App\Http\Controllers\Operator\ShippingController::class, 'index'])->name('operator-shipping-index');
        Route::get('/shipping/create', [App\Http\Controllers\Operator\ShippingController::class, 'create'])->name('operator-shipping-create');
        Route::post('/shipping/create', [App\Http\Controllers\Operator\ShippingController::class, 'store'])->name('operator-shipping-store');
        Route::get('/shipping/edit/{id}', [App\Http\Controllers\Operator\ShippingController::class, 'edit'])->name('operator-shipping-edit');
        Route::post('/shipping/edit/{id}', [App\Http\Controllers\Operator\ShippingController::class, 'update'])->name('operator-shipping-update');
        Route::delete('/shipping/delete/{id}', [App\Http\Controllers\Operator\ShippingController::class, 'destroy'])->name('operator-shipping-delete');

        //------------ OPERATORSHIPPING ENDS ------------

    });

    //------------ OPERATORGENERAL SETTINGS SECTION ENDS ------------

    //------------ OPERATORHOME PAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:home_page_settings'], function () {

        Route::get('/home-page-settings', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'homepage'])->name('operator-home-page-index');


        //------------ OPERATORHOME PAGE THEMES SECTION ------------

        Route::get('/home-themes', [App\Http\Controllers\Operator\HomePageThemeController::class, 'index'])->name('operator-homethemes-index');
        Route::get('/home-themes/create', [App\Http\Controllers\Operator\HomePageThemeController::class, 'create'])->name('operator-homethemes-create');
        Route::post('/home-themes/store', [App\Http\Controllers\Operator\HomePageThemeController::class, 'store'])->name('operator-homethemes-store');
        Route::get('/home-themes/edit/{id}', [App\Http\Controllers\Operator\HomePageThemeController::class, 'edit'])->name('operator-homethemes-edit');
        Route::put('/home-themes/update/{id}', [App\Http\Controllers\Operator\HomePageThemeController::class, 'update'])->name('operator-homethemes-update');
        Route::get('/home-themes/activate/{id}', [App\Http\Controllers\Operator\HomePageThemeController::class, 'activate'])->name('operator-homethemes-activate');
        Route::get('/home-themes/duplicate/{id}', [App\Http\Controllers\Operator\HomePageThemeController::class, 'duplicate'])->name('operator-homethemes-duplicate');
        Route::delete('/home-themes/delete/{id}', [App\Http\Controllers\Operator\HomePageThemeController::class, 'destroy'])->name('operator-homethemes-delete');

        //------------ OPERATORHOME PAGE THEMES SECTION ENDS ------------

        // FEATURED PROMO SECTION REMOVED - Feature deleted
        // ANNOUNCEMENT SECTION REMOVED - Feature deleted

        //------------ OPERATORBRAND SECTION ------------

        Route::get('/brand/datatables', [App\Http\Controllers\Operator\BrandController::class, 'datatables'])->name('operator-brand-datatables');
        Route::get('/brand', [App\Http\Controllers\Operator\BrandController::class, 'index'])->name('operator-brand-index');
        Route::get('/brand/create', [App\Http\Controllers\Operator\BrandController::class, 'create'])->name('operator-brand-create');
        Route::post('/brand/create', [App\Http\Controllers\Operator\BrandController::class, 'store'])->name('operator-brand-store');
        Route::get('/brand/edit/{id}', [App\Http\Controllers\Operator\BrandController::class, 'edit'])->name('operator-brand-edit');
        Route::post('/brand/edit/{id}', [App\Http\Controllers\Operator\BrandController::class, 'update'])->name('operator-brand-update');
        Route::delete('/brand/delete/{id}', [App\Http\Controllers\Operator\BrandController::class, 'destroy'])->name('operator-brand-delete');

        //------------ OPERATORBRAND SECTION ENDS ------------

        //------------ OPERATOR ALTERNATIVES SECTION ------------

        Route::get('/alternatives', [App\Http\Controllers\Operator\AlternativeController::class, 'index'])->name('operator-alternative-index');
        Route::get('/alternatives/search', [App\Http\Controllers\Operator\AlternativeController::class, 'search'])->name('operator-alternative-search');
        Route::get('/alternatives/stats', [App\Http\Controllers\Operator\AlternativeController::class, 'stats'])->name('operator-alternative-stats');
        Route::post('/alternatives/add', [App\Http\Controllers\Operator\AlternativeController::class, 'addAlternative'])->name('operator-alternative-add');
        Route::post('/alternatives/remove', [App\Http\Controllers\Operator\AlternativeController::class, 'removeAlternative'])->name('operator-alternative-remove');

        //------------ OPERATOR ALTERNATIVES SECTION ENDS ------------

    });

    //------------ OPERATORHOME PAGE SETTINGS SECTION ENDS ------------

    Route::group(['middleware' => 'permissions:menu_page_settings'], function () {
        // HELP ARTICLE SECTION REMOVED - Feature deleted
        // STATIC CONTENT SECTION REMOVED - Use pages table for policies

        Route::get('/frontend-setting/contact', [App\Http\Controllers\Operator\FrontendSettingController::class, 'contact'])->name('operator-fs-contact');
        Route::post('/frontend-setting/update/all', [App\Http\Controllers\Operator\FrontendSettingController::class, 'update'])->name('operator-fs-update');
    });

    //------------ OPERATOREMAIL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:email_settings'], function () {

        Route::get('/comms-blueprints/datatables', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'datatables'])->name('operator-mail-datatables');
        Route::get('/comms-blueprints', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'index'])->name('operator-mail-index');
        Route::get('/comms-blueprints/{id}', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'edit'])->name('operator-mail-edit');
        Route::post('/comms-blueprints/{id}', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'update'])->name('operator-mail-update');
        Route::get('/email-config', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'config'])->name('operator-mail-config');
        Route::get('/groupemail', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'groupemail'])->name('operator-group-show');
        Route::post('/groupemailpost', [App\Http\Controllers\Operator\CommsBlueprintController::class, 'groupemailpost'])->name('operator-group-submit');
    });

    if(module("otp")){
        
    Route::group(['middleware' => 'permissions:otp_setting'], function () {
        Route::get('/opt/config', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'otpConfig'])->name('operator-otp-config');
    });

    }

    //------------ OPERATOREMAIL SETTINGS SECTION ENDS ------------

    //------------ OPERATORPAYMENT SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:payment_settings'], function () {

        // Payment Informations

        Route::get('/payment-informations', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'paymentsinfo'])->name('operator-gs-payments');

        // Merchant Payments

        Route::get('/merchant-payment/datatables', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'datatables'])->name('operator-merchant-payment-datatables'); //JSON REQUEST
        Route::get('/merchant-payment', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'index'])->name('operator-merchant-payment-index');
        Route::get('/merchant-payment/create', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'create'])->name('operator-merchant-payment-create');
        Route::post('/merchant-payment/create', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'store'])->name('operator-merchant-payment-store');
        Route::get('/merchant-payment/edit/{id}', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'edit'])->name('operator-merchant-payment-edit');
        Route::post('/merchant-payment/update/{id}', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'update'])->name('operator-merchant-payment-update');
        Route::delete('/merchant-payment/delete/{id}', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'destroy'])->name('operator-merchant-payment-delete');
        Route::get('/merchant-payment/status/{field}/{id1}/{id2}', [App\Http\Controllers\Operator\MerchantPaymentController::class, 'status'])->name('operator-merchant-payment-status');

        // Monetary Unit Settings

        // MULTIPLE MONETARY UNITS

        Route::get('/monetary-unit/datatables', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'datatables'])->name('operator-monetary-unit-datatables'); //JSON REQUEST
        Route::get('/monetary-unit', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'index'])->name('operator-monetary-unit-index');
        Route::get('/monetary-unit/create', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'create'])->name('operator-monetary-unit-create');
        Route::post('/monetary-unit/create', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'store'])->name('operator-monetary-unit-store');
        Route::get('/monetary-unit/edit/{id}', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'edit'])->name('operator-monetary-unit-edit');
        Route::post('/monetary-unit/update/{id}', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'update'])->name('operator-monetary-unit-update');
        Route::delete('/monetary-unit/delete/{id}', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'destroy'])->name('operator-monetary-unit-delete');
        Route::get('/monetary-unit/status/{id1}/{id2}', [App\Http\Controllers\Operator\MonetaryUnitController::class, 'status'])->name('operator-monetary-unit-status');

    });

    //------------ OPERATORPAYMENT SETTINGS SECTION ENDS------------

    //------------ OPERATORSOCIAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:social_settings'], function () {

        //------------ OPERATOR NETWORK PRESENCE ------------

        Route::get('/network-presence/datatables', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'datatables'])->name('operator-network-presence-datatables'); //JSON REQUEST
        Route::get('/network-presence', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'index'])->name('operator-network-presence-index');
        Route::get('/network-presence/create', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'create'])->name('operator-network-presence-create');
        Route::post('/network-presence/create', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'store'])->name('operator-network-presence-store');
        Route::get('/network-presence/edit/{id}', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'edit'])->name('operator-network-presence-edit');
        Route::post('/network-presence/edit/{id}', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'update'])->name('operator-network-presence-update');
        Route::delete('/network-presence/delete/{id}', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'destroy'])->name('operator-network-presence-delete');
        Route::get('/network-presence/status/{id1}/{id2}', [App\Http\Controllers\Operator\NetworkPresenceController::class, 'status'])->name('operator-network-presence-status');

        //------------ OPERATOR NETWORK PRESENCE ENDS ------------

        // CONNECT CONFIG SECTION REMOVED - OAuth settings now in platform_settings
    });
    //------------ OPERATOR CONNECT CONFIG SECTION ENDS------------

    //------------ OPERATORLANGUAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:language_settings'], function () {

        //  Multiple Language Section

        //  Multiple Language Section Ends

        Route::get('/languages/datatables', [App\Http\Controllers\Operator\LanguageController::class, 'datatables'])->name('operator-lang-datatables'); //JSON REQUEST
        Route::get('/languages', [App\Http\Controllers\Operator\LanguageController::class, 'index'])->name('operator-lang-index');
        Route::get('/languages/create', [App\Http\Controllers\Operator\LanguageController::class, 'create'])->name('operator-lang-create');
        Route::get('/languages/import', [App\Http\Controllers\Operator\LanguageController::class, 'import'])->name('operator-lang-import');
        Route::get('/languages/edit/{id}', [App\Http\Controllers\Operator\LanguageController::class, 'edit'])->name('operator-lang-edit');
        Route::get('/languages/export/{id}', [App\Http\Controllers\Operator\LanguageController::class, 'export'])->name('operator-lang-export');
        Route::post('/languages/create', [App\Http\Controllers\Operator\LanguageController::class, 'store'])->name('operator-lang-store');
        Route::post('/languages/import/create', [App\Http\Controllers\Operator\LanguageController::class, 'importStore'])->name('operator-lang-import-store');
        Route::post('/languages/edit/{id}', [App\Http\Controllers\Operator\LanguageController::class, 'update'])->name('operator-lang-update');
        Route::get('/languages/status/{id1}/{id2}', [App\Http\Controllers\Operator\LanguageController::class, 'status'])->name('operator-lang-st');
        Route::delete('/languages/delete/{id}', [App\Http\Controllers\Operator\LanguageController::class, 'destroy'])->name('operator-lang-delete');


        //------------ OPERATORLANGUAGE SETTINGS SECTION ENDS ------------

    });

    // TYPEFACE SECTION REMOVED - No custom fonts feature
    // SEOTOOL SECTION REMOVED - SEO now in platform_settings

    //------------ OPERATORSTAFF SECTION ------------

    Route::group(['middleware' => 'permissions:manage_staffs'], function () {

        Route::get('/staff/datatables', [App\Http\Controllers\Operator\StaffController::class, 'datatables'])->name('operator-staff-datatables');
        Route::get('/staff', [App\Http\Controllers\Operator\StaffController::class, 'index'])->name('operator-staff-index');
        Route::get('/staff/create', [App\Http\Controllers\Operator\StaffController::class, 'create'])->name('operator-staff-create');
        Route::post('/staff/create', [App\Http\Controllers\Operator\StaffController::class, 'store'])->name('operator-staff-store');
        Route::get('/staff/edit/{id}', [App\Http\Controllers\Operator\StaffController::class, 'edit'])->name('operator-staff-edit');
        Route::post('/staff/update/{id}', [App\Http\Controllers\Operator\StaffController::class, 'update'])->name('operator-staff-update');
        Route::get('/staff/show/{id}', [App\Http\Controllers\Operator\StaffController::class, 'show'])->name('operator-staff-show');
        Route::delete('/staff/delete/{id}', [App\Http\Controllers\Operator\StaffController::class, 'destroy'])->name('operator-staff-delete');
    });

    //------------ OPERATORSTAFF SECTION ENDS------------

    //------------ OPERATORMAILING LIST SECTION ------------

    Route::group(['middleware' => 'permissions:subscribers'], function () {

        Route::get('/mailing-list/datatables', [App\Http\Controllers\Operator\MailingListController::class, 'datatables'])->name('operator-mailing-list-datatables'); //JSON REQUEST
        Route::get('/mailing-list', [App\Http\Controllers\Operator\MailingListController::class, 'index'])->name('operator-mailing-list-index');
        Route::get('/mailing-list/download', [App\Http\Controllers\Operator\MailingListController::class, 'download'])->name('operator-mailing-list-download');
    });

    //------------ OPERATORMAILING LIST ENDS ------------

    // ------------ GLOBAL ----------------------
    Route::post('/general-settings/update/all', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'generalupdate'])->name('operator-gs-update');
    Route::post('/general-settings/update/theme', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'updateTheme'])->name('operator-gs-update-theme');
    Route::post('/general-settings/update/payment', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'generalupdatepayment'])->name('operator-gs-update-payment');
    Route::post('/general-settings/update/mail', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'generalMailUpdate'])->name('operator-gs-update-mail');
    Route::get('/general-settings/status/{field}/{status}', [App\Http\Controllers\Operator\MuaadhSettingController::class, 'status'])->name('operator-gs-status');

    // Note: Status and Feature routes are now in the ADMIN CATALOG ITEM SECTION above

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

        Route::get('/admin-role/datatables', [App\Http\Controllers\Operator\RoleController::class, 'datatables'])->name('operator-role-datatables');
        Route::get('/admin-role', [App\Http\Controllers\Operator\RoleController::class, 'index'])->name('operator-role-index');
        Route::get('/admin-role/create', [App\Http\Controllers\Operator\RoleController::class, 'create'])->name('operator-role-create');
        Route::post('/admin-role/create', [App\Http\Controllers\Operator\RoleController::class, 'store'])->name('operator-role-store');
        Route::get('/admin-role/edit/{id}', [App\Http\Controllers\Operator\RoleController::class, 'edit'])->name('operator-role-edit');
        Route::post('/admin-role/edit/{id}', [App\Http\Controllers\Operator\RoleController::class, 'update'])->name('operator-role-update');
        Route::delete('/admin-role/delete/{id}', [App\Http\Controllers\Operator\RoleController::class, 'destroy'])->name('operator-role-delete');

        // ------------ ADMIN ROLE SECTION ENDS ----------------------

        // ------------ MODULE SECTION ----------------------

        Route::get('/module/datatables', [App\Http\Controllers\Operator\ModuleController::class, 'datatables'])->name('operator-module-datatables');
        Route::get('/module', [App\Http\Controllers\Operator\ModuleController::class, 'index'])->name('operator-module-index');
        Route::get('/module/create', [App\Http\Controllers\Operator\ModuleController::class, 'create'])->name('operator-module-create');
        Route::post('/module/install', [App\Http\Controllers\Operator\ModuleController::class, 'install'])->name('operator-module-install');
        Route::get('/module/uninstall/{id}', [App\Http\Controllers\Operator\ModuleController::class, 'uninstall'])->name('operator-module-uninstall');

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
            Route::get('/dashboard', [App\Http\Controllers\Merchant\MerchantController::class, 'index'])->name('merchant.dashboard');

            // TRUST BADGE - يمكن لجميع التجار رفع مستندات التوثيق
            Route::get('/trust-badge', [App\Http\Controllers\Merchant\MerchantController::class, 'trustBadge'])->name('merchant-trust-badge');
            Route::get('/warning/trust-badge/{id}', [App\Http\Controllers\Merchant\MerchantController::class, 'warningTrustBadge'])->name('merchant-warning');
            Route::post('/trust-badge', [App\Http\Controllers\Merchant\MerchantController::class, 'trustBadgeSubmit'])->name('merchant-trust-badge-submit');

            // PROFILE - يمكن لجميع التجار رؤية وتعديل بروفايلهم
            Route::get('/profile', [App\Http\Controllers\Merchant\MerchantController::class, 'profile'])->name('merchant-profile');
            Route::post('/profile', [App\Http\Controllers\Merchant\MerchantController::class, 'profileupdate'])->name('merchant-profile-update');

            // MERCHANT LOGO - شعار التاجر للفواتير
            Route::get('/logo', [App\Http\Controllers\Merchant\MerchantController::class, 'logo'])->name('merchant-logo');
            Route::post('/logo', [App\Http\Controllers\Merchant\MerchantController::class, 'logoUpdate'])->name('merchant-logo-update');
            Route::delete('/logo', [App\Http\Controllers\Merchant\MerchantController::class, 'logoDelete'])->name('merchant-logo-delete');
        });

        // ============ TRUSTED MERCHANT ONLY ============
        // هذه المسارات متاحة فقط للتجار الموثقين (is_merchant = 2)
        Route::group(['middleware' => ['merchant', 'trusted.merchant']], function () {

            //------------ PURCHASE SECTION ------------

            Route::get('/purchases/datatables', [App\Http\Controllers\Merchant\PurchaseController::class, 'datatables'])->name('merchant-purchase-datatables');
            Route::get('/purchases', [App\Http\Controllers\Merchant\PurchaseController::class, 'index'])->name('merchant-purchase-index');
            Route::get('/purchase/{id}/show', [App\Http\Controllers\Merchant\PurchaseController::class, 'show'])->name('merchant-purchase-show');
            Route::get('/purchase/{id}/invoice', [App\Http\Controllers\Merchant\PurchaseController::class, 'invoice'])->name('merchant-purchase-invoice');
            Route::get('/purchase/{id}/print', [App\Http\Controllers\Merchant\PurchaseController::class, 'printpage'])->name('merchant-purchase-print');
            Route::get('/purchase/{id1}/status/{status}', [App\Http\Controllers\Merchant\PurchaseController::class, 'status'])->name('merchant-purchase-status');
            Route::post('/purchase/email/', [App\Http\Controllers\Merchant\PurchaseController::class, 'emailsub'])->name('merchant-purchase-emailsub');

            //------------ PURCHASE SECTION ENDS------------

            Route::get('delivery/datatables', [App\Http\Controllers\Merchant\DeliveryController::class, 'datatables'])->name('merchant-delivery-purchase-datatables');
            Route::get('delivery', [App\Http\Controllers\Merchant\DeliveryController::class, 'index'])->name('merchant.delivery.index');
            Route::get('delivery/boy/find', [App\Http\Controllers\Merchant\DeliveryController::class, 'findCourier'])->name('merchant.find.courier');
            Route::post('courier/search/submit', [App\Http\Controllers\Merchant\DeliveryController::class, 'findCourierSubmit'])->name('merchant-courier-search-submit');

            // Tryoto Shipping Routes
            Route::get('delivery/shipping-options', [App\Http\Controllers\Merchant\DeliveryController::class, 'getShippingOptions'])->name('merchant.shipping.options');
            Route::post('delivery/send-to-tryoto', [App\Http\Controllers\Merchant\DeliveryController::class, 'sendToTryoto'])->name('merchant.send.tryoto');
            Route::get('delivery/track-shipment', [App\Http\Controllers\Merchant\DeliveryController::class, 'trackShipment'])->name('merchant.track.shipment');
            Route::get('delivery/shipment-history/{purchaseId}', [App\Http\Controllers\Merchant\DeliveryController::class, 'shipmentHistory'])->name('merchant.shipment.history');
            Route::post('delivery/cancel-shipment', [App\Http\Controllers\Merchant\DeliveryController::class, 'cancelShipment'])->name('merchant.cancel.shipment');
            Route::post('delivery/ready-for-courier', [App\Http\Controllers\Merchant\DeliveryController::class, 'markReadyForCourierCollection'])->name('merchant.ready.courier');
            Route::post('delivery/handover-to-courier', [App\Http\Controllers\Merchant\DeliveryController::class, 'confirmHandoverToCourier'])->name('merchant.handover.courier');
            Route::get('delivery/stats', [App\Http\Controllers\Merchant\DeliveryController::class, 'shippingStats'])->name('merchant.shipping.stats');
            Route::get('delivery/purchase-status/{purchaseId}', [App\Http\Controllers\Merchant\DeliveryController::class, 'getPurchaseShipmentStatus'])->name('merchant.purchase.shipment.status');

            // Dynamic Shipping Provider Routes
            Route::get('delivery/shipping-providers', [App\Http\Controllers\Merchant\DeliveryController::class, 'getShippingProviders'])->name('merchant.shipping.providers');
            Route::get('delivery/provider-options', [App\Http\Controllers\Merchant\DeliveryController::class, 'getProviderShippingOptions'])->name('merchant.provider.shipping.options');
            Route::post('delivery/send-provider-shipping', [App\Http\Controllers\Merchant\DeliveryController::class, 'sendProviderShipping'])->name('merchant.send.provider.shipping');
            Route::get('delivery/couriers', [App\Http\Controllers\Merchant\DeliveryController::class, 'findCourier'])->name('merchant.delivery.couriers');
            Route::get('delivery/merchant-branches', [App\Http\Controllers\Merchant\DeliveryController::class, 'getMerchantBranches'])->name('merchant.delivery.branches');

            //------------ MERCHANT CATALOG ITEM SECTION ------------

            Route::get('/catalog-items/datatables', [App\Http\Controllers\Merchant\CatalogItemController::class, 'datatables'])->name('merchant-catalog-item-datatables');
            Route::get('/catalog-items', [App\Http\Controllers\Merchant\CatalogItemController::class, 'index'])->name('merchant-catalog-item-index');

            // CREATE SECTION
            Route::get('/catalog-items/search-item', [App\Http\Controllers\Merchant\CatalogItemController::class, 'searchItem'])->name('merchant-catalog-item-search-item');
            Route::get('/catalog-items/{slug}/create', [App\Http\Controllers\Merchant\CatalogItemController::class, 'create'])->name('merchant-catalog-item-create');
            Route::post('/catalog-items/store', [App\Http\Controllers\Merchant\CatalogItemController::class, 'store'])->name('merchant-catalog-item-store');

            // EDIT SECTION
            Route::get('/catalog-items/edit/{merchantItemId}', [App\Http\Controllers\Merchant\CatalogItemController::class, 'edit'])->name('merchant-catalog-item-edit');
            Route::post('/catalog-items/edit/{merchantItemId}', [App\Http\Controllers\Merchant\CatalogItemController::class, 'update'])->name('merchant-catalog-item-update');

            // STATUS SECTION
            Route::get('/catalog-items/status/{id1}/{id2}', [App\Http\Controllers\Merchant\CatalogItemController::class, 'status'])->name('merchant-catalog-item-status');

            // DELETE SECTION
            Route::delete('/catalog-items/delete/{id}', [App\Http\Controllers\Merchant\CatalogItemController::class, 'destroy'])->name('merchant-catalog-item-delete');

            //------------ MERCHANT CATALOG ITEM SECTION ENDS------------

            //------------ STOCK MANAGEMENT SECTION (Merchant #1 only) ------------
            Route::get('/stock/management', [App\Http\Controllers\Merchant\StockManagementController::class, 'index'])->name('merchant-stock-management');
            Route::get('/stock/datatables', [App\Http\Controllers\Merchant\StockManagementController::class, 'datatables'])->name('merchant-stock-datatables');
            Route::get('/stock/export', [App\Http\Controllers\Merchant\StockManagementController::class, 'export'])->name('merchant-stock-export');
            Route::get('/stock/download/{id}', [App\Http\Controllers\Merchant\StockManagementController::class, 'download'])->name('merchant-stock-download');
            Route::post('/stock/full-refresh', [App\Http\Controllers\Merchant\StockManagementController::class, 'triggerFullRefresh'])->name('merchant-stock-full-refresh');
            Route::post('/stock/process-full-refresh', [App\Http\Controllers\Merchant\StockManagementController::class, 'processFullRefresh'])->name('merchant-stock-process-full-refresh');
            Route::get('/stock/progress/{id}', [App\Http\Controllers\Merchant\StockManagementController::class, 'getUpdateProgress'])->name('merchant-stock-progress');
            //------------ STOCK MANAGEMENT SECTION ENDS ------------

            //------------ MERCHANT MY ITEM IMAGES SECTION ------------
            Route::get('/my-items/images', [App\Http\Controllers\Merchant\MyItemImageController::class, 'index'])->name('merchant-my-item-images');
            Route::get('/my-items/images/datatables', [App\Http\Controllers\Merchant\MyItemImageController::class, 'datatables'])->name('merchant-my-item-images-datatables');
            Route::get('/my-items/images/{id}', [App\Http\Controllers\Merchant\MyItemImageController::class, 'show'])->name('merchant-my-item-images-show');
            Route::post('/my-items/images', [App\Http\Controllers\Merchant\MyItemImageController::class, 'store'])->name('merchant-my-item-images-store');
            Route::post('/my-items/images/{id}', [App\Http\Controllers\Merchant\MyItemImageController::class, 'update'])->name('merchant-my-item-images-update');
            Route::delete('/my-items/images/{id}', [App\Http\Controllers\Merchant\MyItemImageController::class, 'destroy'])->name('merchant-my-item-images-delete');
            //------------ MERCHANT MY ITEM IMAGES SECTION ENDS------------

            //------------ MERCHANT SHIPPING ------------

            Route::get('/shipping/datatables', [App\Http\Controllers\Merchant\ShippingController::class, 'datatables'])->name('merchant-shipping-datatables');
            Route::get('/shipping', [App\Http\Controllers\Merchant\ShippingController::class, 'index'])->name('merchant-shipping-index');
            Route::get('/shipping/create', [App\Http\Controllers\Merchant\ShippingController::class, 'create'])->name('merchant-shipping-create');
            Route::post('/shipping/create', [App\Http\Controllers\Merchant\ShippingController::class, 'store'])->name('merchant-shipping-store');
            Route::get('/shipping/edit/{id}', [App\Http\Controllers\Merchant\ShippingController::class, 'edit'])->name('merchant-shipping-edit');
            Route::post('/shipping/edit/{id}', [App\Http\Controllers\Merchant\ShippingController::class, 'update'])->name('merchant-shipping-update');
            Route::delete('/shipping/delete/{id}', [App\Http\Controllers\Merchant\ShippingController::class, 'destroy'])->name('merchant-shipping-delete');

            //------------ MERCHANT SHIPPING ENDS ------------

            //------------ MERCHANT CATALOG EVENT SECTION ------------

            Route::get('/purchase/event/show/{id}', [App\Http\Controllers\Merchant\CatalogEventController::class, 'showPurchaseEvents'])->name('merchant-purchase-event-show');
            Route::get('/purchase/event/count/{id}', [App\Http\Controllers\Merchant\CatalogEventController::class, 'countPurchaseEvents'])->name('merchant-purchase-event-count');
            Route::get('/purchase/event/clear/{id}', [App\Http\Controllers\Merchant\CatalogEventController::class, 'clearPurchaseEvents'])->name('merchant-purchase-event-clear');

            //------------ MERCHANT CATALOG EVENT SECTION ENDS ------------

            Route::get('/withdraw/datatables', [App\Http\Controllers\Merchant\WithdrawController::class, 'datatables'])->name('merchant-wt-datatables');
            Route::get('/withdraw', [App\Http\Controllers\Merchant\WithdrawController::class, 'index'])->name('merchant-wt-index');
            Route::get('/withdraw/create', [App\Http\Controllers\Merchant\WithdrawController::class, 'create'])->name('merchant-wt-create');
            Route::post('/withdraw/create', [App\Http\Controllers\Merchant\WithdrawController::class, 'store'])->name('merchant-wt-store');

            //------------ MERCHANT BRANCH (Warehouse/Origin) ------------
            Route::get('/branch/datatables', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'datatables'])->name('merchant-branch-datatables');
            Route::get('/branch', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'index'])->name('merchant-branch-index');
            Route::get('/branch/create', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'create'])->name('merchant-branch-create');
            Route::post('/branch/create', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'store'])->name('merchant-branch-store');
            Route::get('/branch/edit/{id}', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'edit'])->name('merchant-branch-edit');
            Route::post('/branch/edit/{id}', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'update'])->name('merchant-branch-update');
            Route::get('/branch/delete/{id}', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'destroy'])->name('merchant-branch-delete');
            Route::get('/branch/status/{id}/{status}', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'status'])->name('merchant-branch-status');
            Route::get('/branch/cities', [App\Http\Controllers\Merchant\MerchantBranchController::class, 'getCitiesByCountry'])->name('merchant-branch-get-cities');

            //------------ MERCHANT BRANCH END ------------

            //------------ MERCHANT NETWORK PRESENCE ------------

            Route::get('/network-presence/datatables', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'datatables'])->name('merchant-network-presence-datatables'); //JSON REQUEST
            Route::get('/network-presence', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'index'])->name('merchant-network-presence-index');
            Route::get('/network-presence/create', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'create'])->name('merchant-network-presence-create');
            Route::post('/network-presence/create', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'store'])->name('merchant-network-presence-store');
            Route::get('/network-presence/edit/{id}', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'edit'])->name('merchant-network-presence-edit');
            Route::post('/network-presence/edit/{id}', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'update'])->name('merchant-network-presence-update');
            Route::delete('/network-presence/delete/{id}', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'destroy'])->name('merchant-network-presence-delete');
            Route::get('/network-presence/status/{id1}/{id2}', [App\Http\Controllers\Merchant\NetworkPresenceController::class, 'status'])->name('merchant-network-presence-status');

            //------------ MERCHANT NETWORK PRESENCE ENDS ------------

            //------------ MERCHANT SHIPMENT TRACKING (NEW SYSTEM) ------------

            Route::get('/shipment-tracking', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'index'])->name('merchant.shipment-tracking.index');
            Route::get('/shipment-tracking/{purchaseId}', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'show'])->name('merchant.shipment-tracking.show');
            Route::put('/shipment-tracking/{purchaseId}', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'updateStatus'])->name('merchant.shipment-tracking.update');
            Route::post('/shipment-tracking/{purchaseId}/start', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'startManualShipment'])->name('merchant.shipment-tracking.start');
            Route::get('/shipment-tracking/{purchaseId}/refresh', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'refreshFromApi'])->name('merchant.shipment-tracking.refresh');
            Route::get('/shipment-tracking/{purchaseId}/history', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'getHistory'])->name('merchant.shipment-tracking.history');
            Route::get('/shipment-tracking-stats', [App\Http\Controllers\Merchant\ShipmentTrackingController::class, 'stats'])->name('merchant.shipment-tracking.stats');

            //------------ MERCHANT SHIPMENT TRACKING ENDS ------------

            //------------ MERCHANT CREDENTIALS SECTION - REMOVED ------------
            // Credentials are managed by OPERATOR only (not merchants)
            // See: operator/merchant-credentials routes instead
            //------------ MERCHANT CREDENTIALS SECTION ENDS------------

            // -------------------------- Merchant Income ------------------------------------//
            Route::get('earning/datatables', [App\Http\Controllers\Merchant\IncomeController::class, 'datatables'])->name('merchant.income.datatables');
            Route::get('total/earning', [App\Http\Controllers\Merchant\IncomeController::class, 'index'])->name('merchant.income');
            Route::get('tax-report', [App\Http\Controllers\Merchant\IncomeController::class, 'taxReport'])->name('merchant.tax-report');
            Route::get('statement', [App\Http\Controllers\Merchant\IncomeController::class, 'statement'])->name('merchant.statement');
            Route::get('statement/pdf', [App\Http\Controllers\Merchant\IncomeController::class, 'statementPdf'])->name('merchant.statement.pdf');
            Route::get('monthly-ledger', [App\Http\Controllers\Merchant\IncomeController::class, 'monthlyLedger'])->name('merchant.monthly-ledger');
            Route::get('monthly-ledger/pdf', [App\Http\Controllers\Merchant\IncomeController::class, 'monthlyLedgerPdf'])->name('merchant.monthly-ledger.pdf');
            Route::get('payouts', [App\Http\Controllers\Merchant\IncomeController::class, 'payouts'])->name('merchant.payouts');

        });
    });

    // ************************************ MERCHANT SECTION ENDS**********************************************

    // ************************************ USER SECTION **********************************************

    Route::get('user/success/{status}', function ($status) {
        return view('user.success', compact('status'));
    })->name('user.success');

    Route::prefix('user')->group(function () {
        
        

        // USER AUTH SECION
        Route::get('/login', [App\Http\Controllers\User\LoginController::class, 'showLoginForm'])->name('user.login');
        Route::get('/login/with/otp', [App\Http\Controllers\User\LoginController::class, 'showOtpLoginForm'])->name('user.otp.login');
        Route::post('/login/with/otp/submit', [App\Http\Controllers\User\LoginController::class, 'showOtpLoginFormSubmit'])->name('user.opt.login.submit');
        Route::get('/login/with/otp/view', [App\Http\Controllers\User\LoginController::class, 'showOtpLoginFormView'])->name('user.opt.login.view');
        Route::post('/login/with/otp/view/submit', [App\Http\Controllers\User\LoginController::class, 'showOtpLoginFormViewSubmit'])->name('user.opt.login.view.submit');
        Route::get('/merchant-login', [App\Http\Controllers\User\LoginController::class, 'showMerchantLoginForm'])->name('merchant.login');

        Route::get('/register', [App\Http\Controllers\User\RegisterController::class, 'showRegisterForm'])->name('user.register');
        Route::get('/merchant-register', [App\Http\Controllers\User\RegisterController::class, 'showMerchantRegisterForm'])->name('merchant.register');
        // User Login
        Route::post('/login', [App\Http\Controllers\Auth\User\LoginController::class, 'login'])->name('user.login.submit');
        // User Login End

        // User Register
        Route::post('/register', [App\Http\Controllers\Auth\User\RegisterController::class, 'register'])->name('user-register-submit');
        Route::get('/register/verify/{token}', [App\Http\Controllers\Auth\User\RegisterController::class, 'token'])->name('user-register-token');
        // User Register End

        //------------ USER FORGOT SECTION ------------
        Route::get('/forgot', [App\Http\Controllers\Auth\User\ForgotController::class, 'index'])->name('user.forgot');
        Route::post('/forgot', [App\Http\Controllers\Auth\User\ForgotController::class, 'forgot'])->name('user.forgot.submit');
        Route::get('/change-password/{token}', [App\Http\Controllers\Auth\User\ForgotController::class, 'showChangePassForm'])->name('user.change.token');
        Route::post('/change-password', [App\Http\Controllers\Auth\User\ForgotController::class, 'changepass'])->name('user.change.password');

        //------------ USER FORGOT SECTION ENDS ------------

        Route::get('/logout', [App\Http\Controllers\User\LoginController::class, 'logout'])->name('user-logout');
        Route::get('/dashboard', [App\Http\Controllers\User\UserController::class, 'index'])->name('user-dashboard');

        // Merchant Application (for regular users to become merchants)
        Route::get('/apply-merchant', [App\Http\Controllers\User\UserController::class, 'applyMerchant'])->name('user.apply-merchant');
        Route::post('/apply-merchant', [App\Http\Controllers\User\UserController::class, 'submitMerchantApplication'])->name('user.apply-merchant-submit');

        // User Reset
        Route::get('/reset', [App\Http\Controllers\User\UserController::class, 'resetform'])->name('user-reset');
        Route::post('/reset', [App\Http\Controllers\User\UserController::class, 'reset'])->name('user-reset-submit');
        // User Reset End

        // User Profile
        Route::get('/profile', [App\Http\Controllers\User\UserController::class, 'profile'])->name('user-profile');
        Route::post('/profile', [App\Http\Controllers\User\UserController::class, 'profileupdate'])->name('user-profile-update');
        Route::get('/packages', [App\Http\Controllers\User\UserController::class, 'packages'])->name('user-package');
        // User Profile Ends

        // Get cities by country (states removed) - moved to GeocodingController
        Route::get('/country/wise/city/{country_id}', [\App\Http\Controllers\Api\GeocodingController::class, 'getCitiesByCountry'])->name('country.wise.city');
        Route::get('/user/country/wise/city', [\App\Http\Controllers\Api\GeocodingController::class, 'getCitiesByCountry'])->name('country.wise.city.user');

        // User Favorites
        Route::get('/favorites', [App\Http\Controllers\User\FavoriteController::class, 'favorites'])->name('user-favorites');

        Route::get('/favorite/add/merchant/{merchantItemId}', [App\Http\Controllers\User\FavoriteController::class, 'add'])->name('user-favorite-add-merchant');
        Route::get('/favorite/remove/{id}', [App\Http\Controllers\User\FavoriteController::class, 'remove'])->name('user-favorite-remove');
        // User Favorites Ends

        // User Purchases

        Route::get('/purchases', [App\Http\Controllers\User\PurchaseController::class, 'purchases'])->name('user-purchases');
        Route::get('/purchase/tracking', [App\Http\Controllers\User\PurchaseController::class, 'purchasetrack'])->name('user-purchase-track');
        Route::get('/purchase/trackings/{id}', [App\Http\Controllers\User\PurchaseController::class, 'trackload'])->name('user-purchase-track-search');
        Route::get('/purchase/{id}', [App\Http\Controllers\User\PurchaseController::class, 'purchase'])->name('user-purchase');
        Route::get('/download/purchase/{slug}/{id}', [App\Http\Controllers\User\PurchaseController::class, 'purchasedownload'])->name('user-purchase-download');
        Route::get('print/purchase/print/{id}', [App\Http\Controllers\User\PurchaseController::class, 'purchaseprint'])->name('user-purchase-print');
        Route::get('/json/trans', [App\Http\Controllers\User\PurchaseController::class, 'trans']);
        Route::post('/purchase/{id}/confirm-delivery', [App\Http\Controllers\User\PurchaseController::class, 'confirmDeliveryReceipt'])->name('user-confirm-delivery');

        // User Purchases Ends

        // User Merchant Chat

        Route::post('/user/contact', [App\Http\Controllers\User\ChatController::class, 'usercontact'])->name('user-contact');
        Route::get('/chats', [App\Http\Controllers\User\ChatController::class, 'messages'])->name('user-chats');
        Route::get('/chat/{id}', [App\Http\Controllers\User\ChatController::class, 'message'])->name('user-chat');
        Route::post('/chat/post', [App\Http\Controllers\User\ChatController::class, 'postmessage'])->name('user-chat-post');
        Route::get('/chat/{id}/delete', [App\Http\Controllers\User\ChatController::class, 'messagedelete'])->name('user-chat-delete');
        Route::get('/chat/load/{id}', [App\Http\Controllers\User\ChatController::class, 'msgload'])->name('user-chat-load');

        // User Merchant Chat Ends

        // User Support Tickets

        // Tickets
        Route::get('admin/tickets', [App\Http\Controllers\User\ChatController::class, 'adminmessages'])->name('user-ticket-index');
        // Disputes
        Route::get('admin/disputes', [App\Http\Controllers\User\ChatController::class, 'adminDiscordmessages'])->name('user-dispute-index');

        Route::get('admin/ticket/{id}', [App\Http\Controllers\User\ChatController::class, 'adminmessage'])->name('user-ticket-show');
        Route::post('admin/ticket/post', [App\Http\Controllers\User\ChatController::class, 'adminpostmessage'])->name('user-ticket-store');
        Route::get('admin/ticket/{id}/delete', [App\Http\Controllers\User\ChatController::class, 'adminmessagedelete'])->name('user-ticket-delete');
        Route::post('admin/user/send/ticket', [App\Http\Controllers\User\ChatController::class, 'adminusercontact'])->name('user-send-ticket');
        Route::get('admin/ticket/load/{id}', [App\Http\Controllers\User\ChatController::class, 'messageload'])->name('user-ticket-load');
        // User Support Tickets Ends

        Route::get('/affilate/program', [App\Http\Controllers\User\UserController::class, 'affilate_code'])->name('user-affilate-program');

        Route::get('/affilate/withdraw', [App\Http\Controllers\User\WithdrawController::class, 'index'])->name('user-wwt-index');
        Route::get('/affilate/withdraw/create', [App\Http\Controllers\User\WithdrawController::class, 'create'])->name('user-wwt-create');
        Route::post('/affilate/withdraw/create', [App\Http\Controllers\User\WithdrawController::class, 'store'])->name('user-wwt-store');

        // User Favorite Seller

        Route::get('/favorite/seller', [App\Http\Controllers\User\UserController::class, 'favorites'])->name('user-favorites');
        Route::get('/favorite/{id1}/{id2}', [App\Http\Controllers\User\UserController::class, 'favorite'])->name('user-favorite');
        Route::get('/favorite/seller/{id}/delete', [App\Http\Controllers\User\UserController::class, 'favdelete'])->name('user-favorite-delete');

    });

    // ************************************ USER SECTION ENDS**********************************************

    // ************************************ COURIER SECTION **********************************************
    Route::prefix('courier')->group(function () {

        // COURIER AUTH SECTION
        Route::get('/login', [App\Http\Controllers\Courier\LoginController::class, 'showLoginForm'])->name('courier.login');
        Route::post('/login', [App\Http\Controllers\Auth\Courier\LoginController::class, 'login'])->name('courier.login.submit');
        Route::get('/success/{status}', [App\Http\Controllers\Courier\LoginController::class, 'status'])->name('courier.success');

        Route::get('/register', [App\Http\Controllers\Courier\RegisterController::class, 'showRegisterForm'])->name('courier.register');

        // Courier Register
        Route::post('/register', [App\Http\Controllers\Auth\Courier\RegisterController::class, 'register'])->name('courier-register-submit');
        Route::get('/register/verify/{token}', [App\Http\Controllers\Auth\Courier\RegisterController::class, 'token'])->name('courier-register-token');
        // Courier Register End

        //------------ COURIER FORGOT SECTION ------------
        Route::get('/forgot', [App\Http\Controllers\Auth\Courier\ForgotController::class, 'index'])->name('courier.forgot');
        Route::post('/forgot', [App\Http\Controllers\Auth\Courier\ForgotController::class, 'forgot'])->name('courier.forgot.submit');
        Route::get('/change-password/{token}', [App\Http\Controllers\Auth\Courier\ForgotController::class, 'showChangePassForm'])->name('courier.change.token');
        Route::post('/change-password', [App\Http\Controllers\Auth\Courier\ForgotController::class, 'changepass'])->name('courier.change.password');

        //------------ COURIER FORGOT SECTION ENDS ------------

        Route::get('/logout', [App\Http\Controllers\Courier\LoginController::class, 'logout'])->name('courier-logout');
        Route::get('/dashboard', [App\Http\Controllers\Courier\CourierController::class, 'index'])->name('courier-dashboard');

        Route::get('/profile', [App\Http\Controllers\Courier\CourierController::class, 'profile'])->name('courier-profile');
        Route::post('/profile', [App\Http\Controllers\Courier\CourierController::class, 'profileupdate'])->name('courier-profile-update');

        Route::get('/service/area', [App\Http\Controllers\Courier\CourierController::class, 'serviceArea'])->name('courier-service-area');
        Route::get('/service/area/create', [App\Http\Controllers\Courier\CourierController::class, 'serviceAreaCreate'])->name('courier-service-area-create');
        Route::post('/service/area/create', [App\Http\Controllers\Courier\CourierController::class, 'serviceAreaStore'])->name('courier-service-area-store');
        Route::get('/service/area/edit/{id}', [App\Http\Controllers\Courier\CourierController::class, 'serviceAreaEdit'])->name('courier-service-area-edit');
        Route::post('/service/area/edit/{id}', [App\Http\Controllers\Courier\CourierController::class, 'serviceAreaUpdate'])->name('courier-service-area-update');
        Route::get('/service/area/delete/{id}', [App\Http\Controllers\Courier\CourierController::class, 'serviceAreaDestroy'])->name('courier-service-area-delete');
        Route::get('/service/area/toggle-status/{id}', [App\Http\Controllers\Courier\CourierController::class, 'serviceAreaToggleStatus'])->name('courier-service-area-toggle-status');
        Route::get('/service/area/cities', [App\Http\Controllers\Courier\CourierController::class, 'getCitiesByCountry'])->name('courier-get-cities');

        Route::get('/withdraw', [App\Http\Controllers\Courier\WithdrawController::class, 'index'])->name('courier-wwt-index');
        Route::get('/withdraw/create', [App\Http\Controllers\Courier\WithdrawController::class, 'create'])->name('courier-wwt-create');
        Route::post('/withdraw/create', [App\Http\Controllers\Courier\WithdrawController::class, 'store'])->name('courier-wwt-store');

        Route::get('my/purchases', [App\Http\Controllers\Courier\CourierController::class, 'orders'])->name('courier-purchases');
        Route::get('purchase/details/{id}', [App\Http\Controllers\Courier\CourierController::class, 'orderDetails'])->name('courier-purchase-details');
        Route::get('purchase/delivery/accept/{id}', [App\Http\Controllers\Courier\CourierController::class, 'orderAccept'])->name('courier-purchase-delivery-accept');
        Route::get('purchase/delivery/reject/{id}', [App\Http\Controllers\Courier\CourierController::class, 'orderReject'])->name('courier-purchase-delivery-reject');
        Route::get('purchase/delivery/complete/{id}', [App\Http\Controllers\Courier\CourierController::class, 'orderComplete'])->name('courier-purchase-delivery-complete');

        Route::get('/reset', [App\Http\Controllers\Courier\CourierController::class, 'resetform'])->name('courier-reset');
        Route::post('/reset', [App\Http\Controllers\Courier\CourierController::class, 'reset'])->name('courier-reset-submit');

        // Financial & Accounting Routes
        Route::get('/transactions', [App\Http\Controllers\Courier\CourierController::class, 'transactions'])->name('courier-transactions');
        Route::get('/settlements', [App\Http\Controllers\Courier\CourierController::class, 'settlements'])->name('courier-settlements');
        Route::get('/financial-report', [App\Http\Controllers\Courier\CourierController::class, 'financialReport'])->name('courier-financial-report');
    });

    // ************************************ COURIER SECTION ENDS**********************************************

    // ************************************ FRONT SECTION **********************************************


    Route::post('/item/report', [App\Http\Controllers\Front\CatalogController::class, 'report'])->name('catalog-item.report');

    Route::get('/', [App\Http\Controllers\Front\FrontendController::class, 'index'])->name('front.index');
    // Route removed - extraIndex merged into index() with section-based rendering

    // ALL CATALOGS PAGE (with pagination)
    Route::get('/catalogs', [App\Http\Controllers\Front\FrontendController::class, 'allCatalogs'])->name('front.catalogs');

    Route::get('/monetary-unit/{id}', [App\Http\Controllers\Front\FrontendController::class, 'monetaryUnit'])->name('front.monetary-unit');
    Route::get('/language/{id}', [App\Http\Controllers\Front\FrontendController::class, 'language'])->name('front.language');
    Route::get('/purchase/track/{id}', [App\Http\Controllers\Front\FrontendController::class, 'trackload'])->name('front.track.search');

    // SHIPMENT TRACKING SECTION
    // SHIPMENT TRACKING (NEW SYSTEM) - Public tracking page
    Route::get('/tracking', [App\Http\Controllers\User\ShipmentTrackingController::class, 'track'])->name('front.tracking');
    Route::get('/tracking/status', [App\Http\Controllers\User\ShipmentTrackingController::class, 'getStatus'])->name('front.tracking.status');

    // User shipment tracking (requires auth)
    Route::middleware('auth')->group(function() {
        Route::get('/my-shipments', [App\Http\Controllers\User\ShipmentTrackingController::class, 'index'])->name('user.shipment-tracking.index');
        Route::get('/my-shipments/{purchaseId}', [App\Http\Controllers\User\ShipmentTrackingController::class, 'show'])->name('user.shipment-tracking.show');
    });
    // SHIPMENT TRACKING SECTION ENDS

    // PUBLICATION SECTION REMOVED - Feature deleted
    // Stub routes to prevent errors in legacy views
    Route::get('/publications', fn() => redirect()->route('front.index'))->name('front.publications');
    Route::get('/publication/{slug}', fn() => redirect()->route('front.index'))->name('front.publicationshow');

    // HELP ARTICLE SECTION REMOVED - Feature deleted
    // Stub routes to prevent errors in legacy views
    Route::get('/help-article', fn() => redirect()->route('front.index'))->name('front.help-article');
    Route::get('/help-article/{slug}', fn() => redirect()->route('front.index'))->name('front.help-article-show');

    // CONTACT SECTION
    Route::get('/contact', [App\Http\Controllers\Front\FrontendController::class, 'contact'])->name('front.contact');
    Route::post('/contact', [App\Http\Controllers\Front\FrontendController::class, 'contactemail'])->name('front.contact.submit');
    Route::get('/contact/refresh_code', [App\Http\Controllers\Front\FrontendController::class, 'refresh_code']);
    // CONTACT SECTION  ENDS

 
    // CATALOG ITEM AUTO SEARCH SECTION
    Route::get('/autosearch/catalog-item/{slug}', [App\Http\Controllers\Front\FrontendController::class, 'autosearch']);
    // CATALOG ITEM AUTO SEARCH SECTION ENDS

    // CATEGORY SECTION
    Route::get('/categories', [App\Http\Controllers\Front\CatalogController::class, 'categories'])->name('front.categories');

    // NEW: Unified catalog tree with recursive category traversal
    // Shows all items from selected category AND all descendants
    // UNIFIED: 5-level category route
    // Structure: /brands/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}
    // - brand = Brand slug (e.g., "nissan")
    // - catalog = Catalog slug (e.g., "safari-patrol-1997")
    // - cat1/cat2/cat3 = Category slugs (levels 1, 2, 3)
    Route::get('/brands/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}', [App\Http\Controllers\Front\CatalogController::class, 'catalog'])->name('front.catalog');
    Route::get('/category/{brand_slug}/{catalog_slug}/{cat1?}/{cat2?}/{cat3?}', [App\Http\Controllers\Front\CatalogController::class, 'category'])->name('front.catalog.category');

    // AJAX APIs for catalog selector (lightweight on-demand loading)
    Route::get('/api/catalog/catalogs', [App\Http\Controllers\Front\CatalogController::class, 'getCatalogs'])->name('front.api.catalogs');
    Route::get('/api/catalog/tree', [App\Http\Controllers\Front\CatalogController::class, 'getTreeCategories'])->name('front.api.tree');

    // AJAX API for merchant branches (branches are fetched per merchant context only)
    Route::get('/api/merchant/branches', [App\Http\Controllers\Front\CatalogController::class, 'getMerchantBranches'])->name('front.api.merchant.branches');
    // CATALOG SECTION ENDS

    // COMPARE SECTION REMOVED - Feature deleted

    // SEARCH RESULTS PAGE - Shows catalog items matching search query
    // Displays cards with offers button and alternatives (like tree view)
    Route::get('/search', [App\Http\Controllers\Front\SearchResultsController::class, 'index'])->name('front.search-results');

    // PART RESULT PAGE - Shows all offers for a part number
    // NEW: CatalogItem-first approach (one page per part_number, not per merchant_item)
    Route::get('/result/{part_number}', [App\Http\Controllers\Front\PartResultController::class, 'show'])->name('front.part-result');

    // ============ NEW MERCHANT CART SYSTEM (v4) ============
    // Clean, unified cart API - replaces all old cart routes
    // Uses: App\Http\Controllers\Front\MerchantCartController
    // Service: App\Domain\Commerce\Services\Cart\MerchantCartManager
    // ALL operations are Branch-Scoped (except add which infers branch from item)
    Route::prefix('merchant-cart')->name('merchant-cart.')->group(function () {
        // Cart page view (grouped by branch)
        Route::get('/', [App\Http\Controllers\Front\MerchantCartController::class, 'index'])->name('index');

        // Get all branches cart (AJAX for full page)
        Route::get('/all', [App\Http\Controllers\Front\MerchantCartController::class, 'all'])->name('all');

        // Get branch cart summary (AJAX) - requires branch_id
        Route::get('/summary', [App\Http\Controllers\Front\MerchantCartController::class, 'summary'])->name('summary');

        // Cart count (for header badge)
        Route::get('/count', [App\Http\Controllers\Front\MerchantCartController::class, 'count'])->name('count');

        // Get branch IDs in cart
        Route::get('/branches', [App\Http\Controllers\Front\MerchantCartController::class, 'branches'])->name('branches');

        // Get merchant IDs in cart (legacy support)
        Route::get('/merchants', [App\Http\Controllers\Front\MerchantCartController::class, 'merchants'])->name('merchants');

        // Add item to cart (branch inferred from merchant_item_id)
        Route::post('/add', [App\Http\Controllers\Front\MerchantCartController::class, 'add'])->name('add');

        // Update item quantity - requires branch_id
        Route::post('/update', [App\Http\Controllers\Front\MerchantCartController::class, 'update'])->name('update');

        // Increase/Decrease quantity - requires branch_id
        Route::post('/increase', [App\Http\Controllers\Front\MerchantCartController::class, 'increase'])->name('increase');
        Route::post('/decrease', [App\Http\Controllers\Front\MerchantCartController::class, 'decrease'])->name('decrease');

        // Remove item - requires branch_id
        Route::delete('/remove/{key}', [App\Http\Controllers\Front\MerchantCartController::class, 'remove'])->name('remove');
        Route::post('/remove', [App\Http\Controllers\Front\MerchantCartController::class, 'remove'])->name('remove.post');

        // Clear branch items - requires branch_id
        Route::post('/clear-branch', [App\Http\Controllers\Front\MerchantCartController::class, 'clearBranch'])->name('clear-branch');

        // Clear all cart
        Route::post('/clear', [App\Http\Controllers\Front\MerchantCartController::class, 'clear'])->name('clear');
    });
    // ============ END NEW MERCHANT CART SYSTEM ============

    // FAVORITE SECTION
    Route::middleware('auth')->group(function () {
        Route::get('/favorite/add/merchant/{merchantItemId}', [App\Http\Controllers\User\FavoriteController::class, 'addMerchantFavorite'])->name('merchant.favorite.add');
        Route::get('/favorite/remove/merchant/{merchantItemId}', [App\Http\Controllers\User\FavoriteController::class, 'removeMerchantFavorite'])->name('merchant.favorite.remove');
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
    Route::post('/webhooks/tryoto', [App\Http\Controllers\TryotoWebhookController::class, 'handle'])->name('webhooks.tryoto');
    Route::get('/webhooks/tryoto/test', [App\Http\Controllers\TryotoWebhookController::class, 'test'])->name('webhooks.tryoto.test');

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

    // MERCHANT SECTION

    Route::post('/merchant/contact', [App\Http\Controllers\Front\MerchantController::class, 'merchantcontact'])->name('front.merchant.contact');

    // MERCHANT SECTION ENDS

    // SUBSCRIBE SECTION

    Route::post('/subscriber/store', [App\Http\Controllers\Front\FrontendController::class, 'subscribe'])->name('front.subscribe');

    // SUBSCRIBE SECTION ENDS

    // LOGIN WITH FACEBOOK OR GOOGLE SECTION
    Route::get('auth/{provider}', [App\Http\Controllers\Auth\User\SocialRegisterController::class, 'redirectToProvider'])->name('social-provider');
    Route::get('auth/{provider}/callback', [App\Http\Controllers\Auth\User\SocialRegisterController::class, 'handleProviderCallback']);
    // LOGIN WITH FACEBOOK OR GOOGLE SECTION ENDS

    //  CRONJOB

    Route::get('/merchant/subscription/check', [App\Http\Controllers\Front\FrontendController::class, 'subcheck']);

    // CRONJOB ENDS

    Route::post('the/muaadh/ocean/2441139', [App\Http\Controllers\Front\FrontendController::class, 'subscription']);
    Route::get('finalize', [App\Http\Controllers\Front\FrontendController::class, 'finalize']);
    Route::get('update-finalize', [App\Http\Controllers\Front\FrontendController::class, 'updateFinalize']);

    // MERCHANT AND PAGE SECTION
    Route::get('/{slug}', [App\Http\Controllers\Front\MerchantController::class, 'index'])->name('front.merchant');

    // MERCHANT AND PAGE SECTION ENDS

    // ************************************ FRONT SECTION ENDS**********************************************

});




Route::group(['prefix' => 'tryoto'], function () {
    Route::get('set-webhook', [App\Http\Controllers\TryOtoController::class, 'setWebhook'])->name('tryoto.set-webhook');
    Route::post('webhook/callback', [App\Http\Controllers\TryOtoController::class, 'listenWebhook'])->name('tryoto.callback');

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

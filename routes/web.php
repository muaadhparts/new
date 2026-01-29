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





Route::get('/under-maintenance', [Front\FrontendController::class, 'maintenance'])->name('front-maintenance');

Route::prefix('operator')->group(function () {

    //------------ OPERATOR LOGIN SECTION ------------

    Route::get('/login', [Auth\Operator\LoginController::class, 'showForm'])->name('operator.login');
    Route::post('/login', [Auth\Operator\LoginController::class, 'login'])->name('operator.login.submit');
    Route::get('/logout', [Auth\Operator\LoginController::class, 'logout'])->name('operator.logout');

    //------------ OPERATOR LOGIN SECTION ENDS ------------

    //------------ OPERATOR FORGOT SECTION ------------

    Route::get('/forgot', [Auth\Operator\ForgotController::class, 'showForm'])->name('operator.forgot');
    Route::post('/forgot', [Auth\Operator\ForgotController::class, 'forgot'])->name('operator.forgot.submit');
    Route::get('/change-password/{token}', [Auth\Operator\ForgotController::class, 'showChangePassForm'])->name('operator.change.token');
    Route::post('/change-password', [Auth\Operator\ForgotController::class, 'changepass'])->name('operator.change.password');

    //------------ OPERATOR FORGOT SECTION ENDS ------------

    //------------ OPERATOR UNLOCK/PROTECTION SECTION ------------
    Route::get('/unlock', [Operator\UnlockController::class, 'show'])->name('operator.unlock');
    Route::post('/unlock', [Operator\UnlockController::class, 'verify'])->name('operator.unlock.verify');
    Route::get('/lock', [Operator\UnlockController::class, 'lock'])->name('operator.lock')->middleware('auth:operator');
    //------------ OPERATOR UNLOCK SECTION ENDS ------------

    //------------ PROTECTED OPERATOR ROUTES (Require Authentication + Protection) ------------
    Route::middleware(['auth:operator', 'operator.protection'])->group(function () {

        //------------ OPERATOR CATALOG EVENT SECTION ------------
        Route::get('/all/event/count', [Operator\CatalogEventController::class, 'allEventCount'])->name('all-event-count');
        Route::get('/user/event/show', [Operator\CatalogEventController::class, 'showUserEvents'])->name('user-event-show');
        Route::get('/user/event/clear', [Operator\CatalogEventController::class, 'clearUserEvents'])->name('user-event-clear');
        Route::get('/purchase/event/show', [Operator\CatalogEventController::class, 'showPurchaseEvents'])->name('purchase-event-show');
        Route::get('/purchase/event/clear', [Operator\CatalogEventController::class, 'clearPurchaseEvents'])->name('purchase-event-clear');
        Route::get('/catalog-item/event/show', [Operator\CatalogEventController::class, 'showCatalogItemEvents'])->name('catalog-item-event-show');
        Route::get('/catalog-item/event/clear', [Operator\CatalogEventController::class, 'clearCatalogItemEvents'])->name('catalog-item-event-clear');
        Route::get('/conv/event/show', [Operator\CatalogEventController::class, 'showConversationEvents'])->name('conv-event-show');
        Route::get('/conv/event/clear', [Operator\CatalogEventController::class, 'clearConversationEvents'])->name('conv-event-clear');
        //------------ OPERATOR CATALOG EVENT SECTION ENDS ------------

        //------------ OPERATOR DASHBOARD & PROFILE SECTION ------------
        Route::get('/', [Operator\DashboardController::class, 'index'])->name('operator.dashboard');
        Route::get('/profile', [Operator\DashboardController::class, 'profile'])->name('operator.profile');
        Route::post('/profile/update', [Operator\DashboardController::class, 'profileupdate'])->name('operator.profile.update');
        Route::get('/password', [Operator\DashboardController::class, 'passwordreset'])->name('operator.password');
        Route::post('/password/update', [Operator\DashboardController::class, 'changepass'])->name('operator.password.update');
        //------------ OPERATOR DASHBOARD & PROFILE SECTION ENDS ------------

        //------------ OPERATORPERFORMANCE MONITORING SECTION ------------
        Route::get('/performance', [Operator\PerformanceController::class, 'index'])->name('operator-performance');
        Route::get('/performance/slow-queries', [Operator\PerformanceController::class, 'slowQueries'])->name('operator-performance-slow-queries');
        Route::get('/performance/slow-requests', [Operator\PerformanceController::class, 'slowRequests'])->name('operator-performance-slow-requests');
        Route::get('/performance/repeated-queries', [Operator\PerformanceController::class, 'repeatedQueries'])->name('operator-performance-repeated-queries');
        Route::get('/performance/report', [Operator\PerformanceController::class, 'downloadReport'])->name('operator-performance-report');
        Route::get('/performance/api/summary', [Operator\PerformanceController::class, 'apiSummary'])->name('operator-performance-api-summary');
        Route::post('/performance/prune', [Operator\PerformanceController::class, 'pruneOldEntries'])->name('operator-performance-prune');
        //------------ OPERATORPERFORMANCE MONITORING SECTION ENDS ------------

        //------------ OPERATORAPI CREDENTIALS SECTION ------------
        Route::get('/credentials', [Operator\ApiCredentialController::class, 'index'])->name('operator.credentials.index');
        Route::get('/credentials/create', [Operator\ApiCredentialController::class, 'create'])->name('operator.credentials.create');
        Route::post('/credentials', [Operator\ApiCredentialController::class, 'store'])->name('operator.credentials.store');
        Route::get('/credentials/{id}/edit', [Operator\ApiCredentialController::class, 'edit'])->name('operator.credentials.edit');
        Route::put('/credentials/{id}', [Operator\ApiCredentialController::class, 'update'])->name('operator.credentials.update');
        Route::delete('/credentials/{id}', [Operator\ApiCredentialController::class, 'destroy'])->name('operator.credentials.destroy');
        Route::post('/credentials/{id}/toggle', [Operator\ApiCredentialController::class, 'toggle'])->name('operator.credentials.toggle');
        Route::post('/credentials/{id}/test', [Operator\ApiCredentialController::class, 'test'])->name('operator.credentials.test');
        //------------ OPERATORAPI CREDENTIALS SECTION ENDS ------------

        //------------ OPERATORMERCHANT CREDENTIALS SECTION ------------
        Route::get('/merchant-credentials', [Operator\MerchantCredentialController::class, 'index'])->name('operator.merchant-credentials.index');
        Route::get('/merchant-credentials/create', [Operator\MerchantCredentialController::class, 'create'])->name('operator.merchant-credentials.create');
        Route::post('/merchant-credentials', [Operator\MerchantCredentialController::class, 'store'])->name('operator.merchant-credentials.store');
        Route::get('/merchant-credentials/{id}/edit', [Operator\MerchantCredentialController::class, 'edit'])->name('operator.merchant-credentials.edit');
        Route::put('/merchant-credentials/{id}', [Operator\MerchantCredentialController::class, 'update'])->name('operator.merchant-credentials.update');
        Route::delete('/merchant-credentials/{id}', [Operator\MerchantCredentialController::class, 'destroy'])->name('operator.merchant-credentials.destroy');
        Route::post('/merchant-credentials/{id}/toggle', [Operator\MerchantCredentialController::class, 'toggle'])->name('operator.merchant-credentials.toggle');
        Route::post('/merchant-credentials/{id}/test', [Operator\MerchantCredentialController::class, 'test'])->name('operator.merchant-credentials.test');
        //------------ OPERATORMERCHANT CREDENTIALS SECTION ENDS ------------
    });

    //------------ OPERATORPURCHASE SECTION ------------

    Route::group(['middleware' => 'permissions:purchases'], function () {

        Route::get('/purchases/datatables/{slug}', [Operator\PurchaseController::class, 'datatables'])->name('operator-purchase-datatables'); //JSON REQUEST
        Route::get('/purchases', [Operator\PurchaseController::class, 'purchases'])->name('operator-purchases-all');
        Route::get('/purchase/edit/{id}', [Operator\PurchaseController::class, 'edit'])->name('operator-purchase-edit');
        Route::post('/purchase/update/{id}', [Operator\PurchaseController::class, 'update'])->name('operator-purchase-update');
        Route::get('/purchase/{id}/show', [Operator\PurchaseController::class, 'show'])->name('operator-purchase-show');
        Route::get('/purchase/{id}/invoice', [Operator\PurchaseController::class, 'invoice'])->name('operator-purchase-invoice');
        Route::get('/purchase/{id}/print', [Operator\PurchaseController::class, 'printpage'])->name('operator-purchase-print');
        Route::get('/purchase/{id1}/status/{status}', [Operator\PurchaseController::class, 'status'])->name('operator-purchase-status');
        Route::post('/purchase/email/', [Operator\PurchaseController::class, 'emailsub'])->name('operator-purchase-emailsub');
        Route::get('/send-message', [Operator\PurchaseController::class, 'emailsub'])->name('operator-send-message'); // Alias for email modal in user/courier lists
        Route::post('/purchase/catalogItem-submit', [Operator\PurchaseController::class, 'catalogItem_submit'])->name('operator-purchase-catalogItem-submit');
        Route::get('/purchase/catalogItem-show/{id}', [Operator\PurchaseController::class, 'catalogItem_show']);
        // REMOVED: addcart, updatecart - MerchantCart class deleted
        Route::get('/purchasecart/catalogItem-edit/{id}/{itemid}/{purchaseid}', [Operator\PurchaseController::class, 'catalogItem_edit'])->name('operator-purchase-catalogItem-edit');
        Route::get('/purchasecart/catalogItem-delete/{id}/{purchaseid}', [Operator\PurchaseController::class, 'catalogItem_delete'])->name('operator-purchase-catalogItem-delete');
        // Purchase Tracking

        // CREATE PURCHASE

        Route::get('/purchase/catalog-item/datatables', [Operator\PurchaseCreateController::class, 'datatables'])->name('operator-purchase-catalog-item-datatables');
        Route::get('/purchase/create', [Operator\PurchaseCreateController::class, 'create'])->name('operator-purchase-create');
        Route::get('/purchase/catalog-item/add/{catalog_item_id}', [Operator\PurchaseCreateController::class, 'addCatalogItem'])->name('operator-purchase-catalog-item-add');
        Route::get('/purchase/catalog-item/add', [Operator\PurchaseCreateController::class, 'purchaseStore'])->name('operator.purchase.store.new');
        Route::get('/purchase/catalog-item/remove/{catalog_item_id}', [Operator\PurchaseCreateController::class, 'removePurchaseCatalogItem'])->name('operator.purchase.catalog-item.remove');
        Route::get('/purchase/create/catalog-item-show/{id}', [Operator\PurchaseCreateController::class, 'catalog_item_show']);
        // REMOVED: addcart, removecart, CreatePurchaseSubmit - MerchantCart class deleted
        Route::get('/purchase/create/user-address', [Operator\PurchaseCreateController::class, 'userAddress']);
        Route::post('/purchase/create/user-address', [Operator\PurchaseCreateController::class, 'userAddressSubmit'])->name('operator.purchase.create.user.address');
        Route::post('/purchase/create/purchase/view', [Operator\PurchaseCreateController::class, 'viewCreatePurchase'])->name('operator.purchase.create.view');

        Route::get('/purchase/{id}/timeline', [Operator\PurchaseTimelineController::class, 'index'])->name('operator-purchase-timeline');
        Route::get('/purchase/{id}/timelineload', [Operator\PurchaseTimelineController::class, 'load'])->name('operator-purchase-timeline-load');
        Route::post('/purchase/timeline/store', [Operator\PurchaseTimelineController::class, 'store'])->name('operator-purchase-timeline-store');
        Route::get('/purchase/timeline/add', [Operator\PurchaseTimelineController::class, 'add'])->name('operator-purchase-timeline-add');
        Route::get('/purchase/timeline/edit/{id}', [Operator\PurchaseTimelineController::class, 'edit'])->name('operator-purchase-timeline-edit');
        Route::post('/purchase/timeline/update/{id}', [Operator\PurchaseTimelineController::class, 'update'])->name('operator-purchase-timeline-update');
        Route::delete('/purchase/timeline/delete/{id}', [Operator\PurchaseTimelineController::class, 'delete'])->name('operator-purchase-timeline-delete');

        // Purchase Tracking Ends

    });

    //------------ OPERATORPURCHASE SECTION ENDS------------

    //------------ OPERATORSHIPMENTS SECTION ------------

    Route::group(['middleware' => 'permissions:purchases'], function () {
        Route::get('/shipments', [Operator\ShipmentController::class, 'index'])->name('operator.shipments.index');
        Route::get('/shipments/show/{tracking}', [Operator\ShipmentController::class, 'show'])->name('operator.shipments.show');
        Route::get('/shipments/refresh/{tracking}', [Operator\ShipmentController::class, 'refresh'])->name('operator.shipments.refresh');
        Route::post('/shipments/cancel/{tracking}', [Operator\ShipmentController::class, 'cancel'])->name('operator.shipments.cancel');
        Route::get('/shipments/export', [Operator\ShipmentController::class, 'export'])->name('operator.shipments.export');
        Route::post('/shipments/bulk-refresh', [Operator\ShipmentController::class, 'bulkRefresh'])->name('operator.shipments.bulk-refresh');
        Route::get('/shipments/reports', [Operator\ShipmentController::class, 'reports'])->name('operator.shipments.reports');
    });

    //------------ OPERATORSHIPMENTS SECTION ENDS------------

    /////////////////////////////// ////////////////////////////////////////////

    // --------------- ADMIN COUNTRY & CITY SECTION (Protected) ---------------//
    Route::middleware(['auth:operator'])->group(function () {
        Route::get('/country/datatables', [Operator\CountryController::class, 'datatables'])->name('operator-country-datatables');
        Route::get('/manage/country', [Operator\CountryController::class, 'manageCountry'])->name('operator-country-index');
        Route::get('/manage/country/status/{id1}/{id2}', [Operator\CountryController::class, 'status'])->name('operator-country-status');
        Route::get('/country/delete/{id}', [Operator\CountryController::class, 'delete'])->name('operator-country-delete');
        Route::get('/country/tax/datatables', [Operator\CountryController::class, 'taxDatatables'])->name('operator-country-tax-datatables');
        Route::get('/manage/country/tax', [Operator\CountryController::class, 'country_tax'])->name('operator-country-tax');
        Route::get('/country/set-tax/{id}', [Operator\CountryController::class, 'setTax'])->name('operator-set-tax');
        Route::post('/country/set-tax/store/{id}', [Operator\CountryController::class, 'updateTax'])->name('operator-tax-update');

        Route::get('/city/datatables/{country}', [Operator\CityController::class, 'datatables'])->name('operator-city-datatables');
        Route::get('/manage/city/{country}', [Operator\CityController::class, 'managecity'])->name('operator-city-index');
        Route::get('/city/create/{country}', [Operator\CityController::class, 'create'])->name('operator-city-create');
        Route::post('/city/store/{country}', [Operator\CityController::class, 'store'])->name('operator-city-store');
        Route::get('/city/status/{id1}/{id2}', [Operator\CityController::class, 'status'])->name('operator-city-status');
        Route::get('/city/edit/{id}', [Operator\CityController::class, 'edit'])->name('operator-city-edit');
        Route::post('/city/update/{id}', [Operator\CityController::class, 'update'])->name('operator-city-update');
        Route::delete('/city/delete/{id}', [Operator\CityController::class, 'delete'])->name('operator-city-delete');
    });
    // --------------- ADMIN COUNTRY & CITY SECTION ENDS ---------------//

    //------------ OPERATORCATEGORY SECTION ENDS------------

    Route::group(['middleware' => 'permissions:earning'], function () {

        // -------------------------- Admin Total Income Route --------------------------//
        Route::get('tax/calculate', [Operator\IncomeController::class, 'taxCalculate'])->name('operator-tax-calculate-income');
        Route::get('withdraw/earning', [Operator\IncomeController::class, 'withdrawIncome'])->name('operator-withdraw-income');
        Route::get('commission/earning', [Operator\IncomeController::class, 'commissionIncome'])->name('operator-commission-income');
        Route::get('merchant/report', [Operator\IncomeController::class, 'merchantReport'])->name('operator-merchant-report');
        Route::get('commission/detailed', [Operator\IncomeController::class, 'commissionIncomeDetailed'])->name('operator-commission-detailed');
        // -------------------------- Admin Total Income Route --------------------------//
    });

    // ========================== ACCOUNTING LEDGER SYSTEM ==========================//
    Route::group(['prefix' => 'accounts'], function () {
        // Dashboard
        Route::get('/', [Operator\AccountLedgerController::class, 'index'])->name('operator.accounts.index');

        // Parties Lists
        Route::get('/merchants', [Operator\AccountLedgerController::class, 'merchants'])->name('operator.accounts.merchants');
        Route::get('/couriers', [Operator\AccountLedgerController::class, 'couriers'])->name('operator.accounts.couriers');
        Route::get('/shipping', [Operator\AccountLedgerController::class, 'shippingProviders'])->name('operator.accounts.shipping');
        Route::get('/payment', [Operator\AccountLedgerController::class, 'paymentProviders'])->name('operator.accounts.payment');

        // Party Statement
        Route::get('/party/{party}/statement', [Operator\AccountLedgerController::class, 'partyStatement'])->name('operator.accounts.party.statement');
        Route::get('/transaction/{transaction}', [Operator\AccountLedgerController::class, 'transactionDetails'])->name('operator.accounts.transaction');

        // Settlements
        Route::get('/settlements', [Operator\AccountLedgerController::class, 'settlements'])->name('operator.accounts.settlements');
        Route::get('/settlements/create', [Operator\AccountLedgerController::class, 'createSettlementForm'])->name('operator.accounts.settlements.create');
        Route::post('/settlements', [Operator\AccountLedgerController::class, 'storeSettlement'])->name('operator.accounts.settlements.store');
        Route::get('/settlements/{batch}', [Operator\AccountLedgerController::class, 'settlementDetails'])->name('operator.accounts.settlements.show');

        // Courier Settlements - تسويات المناديب
        Route::post('/settlements/courier', [Operator\AccountLedgerController::class, 'courierSettlement'])->name('operator.accounts.settlements.courier');
        Route::get('/settlements/courier/{courierId}/pending', [Operator\AccountLedgerController::class, 'pendingSettlementsByCourier'])->name('operator.accounts.settlements.courier.pending');

        // Shipping Company Settlements - تسويات شركات الشحن
        Route::post('/settlements/shipping', [Operator\AccountLedgerController::class, 'shippingCompanySettlement'])->name('operator.accounts.settlements.shipping');
        Route::get('/settlements/shipping/{providerCode}/pending', [Operator\AccountLedgerController::class, 'pendingSettlementsByProvider'])->name('operator.accounts.settlements.shipping.pending');

        // Sync Parties
        Route::post('/sync-parties', [Operator\AccountLedgerController::class, 'syncParties'])->name('operator.accounts.sync');

        // Reports
        Route::get('/reports/receivables', [Operator\AccountLedgerController::class, 'receivablesReport'])->name('operator.accounts.reports.receivables');
        Route::get('/reports/payables', [Operator\AccountLedgerController::class, 'payablesReport'])->name('operator.accounts.reports.payables');
        Route::get('/reports/shipping', [Operator\AccountLedgerController::class, 'shippingReport'])->name('operator.accounts.reports.shipping');
        Route::get('/reports/payment', [Operator\AccountLedgerController::class, 'paymentReport'])->name('operator.accounts.reports.payment');
        Route::get('/reports/tax', [Operator\AccountLedgerController::class, 'taxReport'])->name('operator.accounts.reports.tax');

        // التقارير المحسنة (من Ledger فقط)
        Route::get('/reports/platform', [Operator\AccountLedgerController::class, 'platformReport'])->name('operator.accounts.reports.platform');
        Route::get('/reports/merchants-summary', [Operator\AccountLedgerController::class, 'merchantsSummary'])->name('operator.accounts.reports.merchants-summary');
        Route::get('/reports/couriers', [Operator\AccountLedgerController::class, 'couriersReport'])->name('operator.accounts.reports.couriers');
        Route::get('/reports/shipping-companies', [Operator\AccountLedgerController::class, 'shippingCompaniesReport'])->name('operator.accounts.reports.shipping-companies');
        Route::get('/reports/receivables-payables', [Operator\AccountLedgerController::class, 'receivablesPayablesReport'])->name('operator.accounts.reports.receivables-payables');

        // كشف حساب التاجر
        Route::get('/merchant-statement/{merchantId}', [Operator\AccountLedgerController::class, 'merchantStatement'])->name('operator.accounts.merchant-statement');

        // شركات الشحن - كشف حساب مفصل
        Route::get('/shipping-companies', [Operator\AccountLedgerController::class, 'shippingCompanyList'])->name('operator.accounts.shipping-companies');
        Route::get('/shipping-company/{providerCode}/statement', [Operator\AccountLedgerController::class, 'shippingCompanyStatement'])->name('operator.accounts.shipping-company.statement');
        Route::get('/shipping-company/{providerCode}/statement/pdf', [Operator\AccountLedgerController::class, 'shippingCompanyStatementPdf'])->name('operator.accounts.shipping-company.statement.pdf');
    });
    // ========================== END ACCOUNTING LEDGER SYSTEM ==========================//

    /////////////////////////////// ////////////////////////////////////////////

    // Note: Old Category/Subcategory/Childcategory and Attribute routes removed - now using TreeCategories

    //------------ OPERATORCATALOG ITEM SECTION ------------

    Route::group(['middleware' => 'permissions:catalog_items'], function () {
        Route::get('/catalog-items/datatables', [Operator\CatalogItemController::class, 'datatables'])->name('operator-catalog-item-datatables');
        Route::get('/catalog-items', [Operator\CatalogItemController::class, 'index'])->name('operator-catalog-item-index');

        // CREATE SECTION
        Route::get('/catalog-items/{slug}/create', [Operator\CatalogItemController::class, 'create'])->name('operator-catalog-item-create');
        Route::post('/catalog-items/store', [Operator\CatalogItemController::class, 'store'])->name('operator-catalog-item-store');

        // EDIT SECTION
        Route::get('/catalog-items/edit/{catalogItemId}', [Operator\CatalogItemController::class, 'edit'])->name('operator-catalog-item-edit');
        Route::post('/catalog-items/edit/{catalogItemId}', [Operator\CatalogItemController::class, 'update'])->name('operator-catalog-item-update');

        // DELETE SECTION
        Route::delete('/catalog-items/delete/{id}', [Operator\CatalogItemController::class, 'destroy'])->name('operator-catalog-item-delete');

        // SETTINGS
        Route::get('/catalog-items/settings', [Operator\CatalogItemController::class, 'catalogItemSettings'])->name('operator-gs-catalog-item-settings');
        Route::post('/catalog-items/settings/update', [Operator\CatalogItemController::class, 'settingUpdate'])->name('operator-gs-catalog-item-settings-update');

        // CATALOG ITEM IMAGES SECTION
        Route::get('/catalog-items/images', [Operator\CatalogItemImageController::class, 'index'])->name('operator-catalog-item-images');
        Route::get('/catalog-items/images/autocomplete', [Operator\CatalogItemImageController::class, 'autocomplete'])->name('operator-catalog-item-images-autocomplete');
        Route::get('/catalog-items/images/{id}', [Operator\CatalogItemImageController::class, 'show'])->name('operator-catalog-item-images-show');
        Route::post('/catalog-items/images/{id}', [Operator\CatalogItemImageController::class, 'update'])->name('operator-catalog-item-images-update');

        // MERCHANT ITEM IMAGES SECTION
        Route::get('/merchant-items/images', [Operator\MerchantItemImageController::class, 'index'])->name('operator-merchant-item-images');
        Route::get('/merchant-items/images/autocomplete', [Operator\MerchantItemImageController::class, 'autocomplete'])->name('operator-merchant-item-images-autocomplete');
        Route::get('/merchant-items/images/merchants', [Operator\MerchantItemImageController::class, 'getMerchants'])->name('operator-merchant-item-images-merchants');
        Route::get('/merchant-items/images/branches', [Operator\MerchantItemImageController::class, 'getBranches'])->name('operator-merchant-item-images-branches');
        Route::get('/merchant-items/images/quality-brands', [Operator\MerchantItemImageController::class, 'getQualityBrands'])->name('operator-merchant-item-images-quality-brands');
        Route::get('/merchant-items/images/photos/{merchant_item_id}', [Operator\MerchantItemImageController::class, 'getPhotos'])->name('operator-merchant-item-images-photos');
        Route::post('/merchant-items/images/store', [Operator\MerchantItemImageController::class, 'store'])->name('operator-merchant-item-images-store');
        Route::delete('/merchant-items/images/{id}', [Operator\MerchantItemImageController::class, 'destroy'])->name('operator-merchant-item-images-delete');
        Route::post('/merchant-items/images/order', [Operator\MerchantItemImageController::class, 'updateOrder'])->name('operator-merchant-item-images-order');
    });

    //------------ OPERATORCATALOG ITEM SECTION ENDS------------


    //------------ OPERATORCATALOGITEM DISCUSSION SECTION ------------

    Route::group(['middleware' => 'permissions:catalogItem_discussion'], function () {

        // CATALOG REVIEW SECTION ------------

        Route::get('/catalog-reviews/datatables', [Operator\CatalogReviewController::class, 'datatables'])->name('operator-catalog-review-datatables'); //JSON REQUEST
        Route::get('/catalog-reviews', [Operator\CatalogReviewController::class, 'index'])->name('operator-catalog-review-index');
        Route::delete('/catalog-reviews/delete/{id}', [Operator\CatalogReviewController::class, 'destroy'])->name('operator-catalog-review-delete');
        Route::get('/catalog-reviews/show/{id}', [Operator\CatalogReviewController::class, 'show'])->name('operator-catalog-review-show');

        // CATALOG REVIEW SECTION ENDS------------

        // BUYER NOTE SECTION ------------

        Route::get('/buyer-notes/datatables', [Operator\BuyerNoteController::class, 'datatables'])->name('operator-buyer-note-datatables'); //JSON REQUEST
        Route::get('/buyer-notes', [Operator\BuyerNoteController::class, 'index'])->name('operator-buyer-note-index');
        Route::delete('/buyer-notes/delete/{id}', [Operator\BuyerNoteController::class, 'destroy'])->name('operator-buyer-note-delete');
        Route::get('/buyer-notes/show/{id}', [Operator\BuyerNoteController::class, 'show'])->name('operator-buyer-note-show');

        // BUYER NOTE SECTION ENDS ------------

        // ABUSE FLAG SECTION ------------

        Route::get('/abuse-flags/datatables', [Operator\AbuseFlagController::class, 'datatables'])->name('operator-abuse-flag-datatables'); //JSON REQUEST
        Route::get('/abuse-flags', [Operator\AbuseFlagController::class, 'index'])->name('operator-abuse-flag-index');
        Route::delete('/abuse-flags/delete/{id}', [Operator\AbuseFlagController::class, 'destroy'])->name('operator-abuse-flag-delete');
        Route::get('/abuse-flags/show/{id}', [Operator\AbuseFlagController::class, 'show'])->name('operator-abuse-flag-show');

        // ABUSE FLAG SECTION ENDS ------------

    });

    //------------ OPERATORPRODUCT DISCUSSION SECTION ENDS ------------

    //------------ OPERATORUSER SECTION ------------

    Route::group(['middleware' => 'permissions:customers'], function () {

        Route::get('/users/datatables', [Operator\UserController::class, 'datatables'])->name('operator-user-datatables'); //JSON REQUEST
        Route::get('/users', [Operator\UserController::class, 'index'])->name('operator-user-index');
        Route::get('/users/create', [Operator\UserController::class, 'create'])->name('operator-user-create');
        Route::post('/users/store', [Operator\UserController::class, 'store'])->name('operator-user-store');
        Route::get('/users/edit/{id}', [Operator\UserController::class, 'edit'])->name('operator-user-edit');
        Route::post('/users/edit/{id}', [Operator\UserController::class, 'update'])->name('operator-user-update');
        Route::delete('/users/delete/{id}', [Operator\UserController::class, 'destroy'])->name('operator-user-delete');
        Route::get('/user/{id}/show', [Operator\UserController::class, 'show'])->name('operator-user-show');
        Route::get('/users/ban/{id1}/{id2}', [Operator\UserController::class, 'ban'])->name('operator-user-ban');
        Route::get('/user/default/image', [Operator\MuaadhSettingController::class, 'user_image'])->name('operator-user-image');
        Route::get('/users/merchant/{id}', [Operator\UserController::class, 'merchant'])->name('operator-user-merchant');
        Route::post('/user/merchant/{id}', [Operator\UserController::class, 'setMerchant'])->name('operator-user-merchant-update');

        //USER WITHDRAW SECTION

        Route::get('/users/withdraws/datatables', [Operator\UserController::class, 'withdrawdatatables'])->name('operator-withdraw-datatables'); //JSON REQUEST
        Route::get('/users/withdraws', [Operator\UserController::class, 'withdraws'])->name('operator-withdraw-index');
        Route::get('/user/withdraw/{id}/show', [Operator\UserController::class, 'withdrawdetails'])->name('operator-withdraw-show');
        Route::get('/users/withdraws/accept/{id}', [Operator\UserController::class, 'accept'])->name('operator-withdraw-accept');
        Route::get('/user/withdraws/reject/{id}', [Operator\UserController::class, 'reject'])->name('operator-withdraw-reject');

        // WITHDRAW SECTION ENDS

        //COURIER WITHDRAW SECTION

        Route::get('/courier/withdraws/datatables', [Operator\CourierController::class, 'withdrawdatatables'])->name('operator-withdraw-courier-datatables'); //JSON REQUEST
        Route::get('/courier/withdraws', [Operator\CourierController::class, 'withdraws'])->name('operator-withdraw-courier-index');
        Route::get('/courier/withdraw/show/{id}', [Operator\CourierController::class, 'withdrawdetails'])->name('operator-withdraw-courier-show');
        Route::get('/courier/withdraw/accept/{id}', [Operator\CourierController::class, 'accept'])->name('operator-withdraw-courier-accept');
        Route::get('/courier/withdraw/reject/{id}', [Operator\CourierController::class, 'reject'])->name('operator-withdraw-courier-reject');

        // WITHDRAW SECTION ENDS

    });

    Route::group(['middleware' => 'permissions:couriers'], function () {

        Route::get('/couriers/datatables', [Operator\CourierController::class, 'datatables'])->name('operator-courier-datatables'); //JSON REQUEST
        Route::get('/couriers', [Operator\CourierController::class, 'index'])->name('operator-courier-index');

        Route::delete('/couriers/delete/{id}', [Operator\CourierController::class, 'destroy'])->name('operator-courier-delete');
        Route::get('/courier/{id}/show', [Operator\CourierController::class, 'show'])->name('operator-courier-show');
        Route::get('/couriers/ban/{id1}/{id2}', [Operator\CourierController::class, 'ban'])->name('operator-courier-ban');
        Route::get('/courier/default/image', [Operator\MuaadhSettingController::class, 'courier_image'])->name('operator-courier-image');

        // WITHDRAW SECTION

        Route::get('/couriers/withdraws/datatables', [Operator\CourierController::class, 'withdrawdatatables'])->name('operator-courier-withdraw-datatables'); //JSON REQUEST
        Route::get('/couriers/withdraws', [Operator\CourierController::class, 'withdraws'])->name('operator-courier-withdraw-index');
        Route::get('/courier/withdraw/{id}/show', [Operator\CourierController::class, 'withdrawdetails'])->name('operator-courier-withdraw-show');
        Route::get('/couriers/withdraws/accept/{id}', [Operator\CourierController::class, 'accept'])->name('operator-courier-withdraw-accept');
        Route::get('/courier/withdraws/reject/{id}', [Operator\CourierController::class, 'reject'])->name('operator-courier-withdraw-reject');

        // WITHDRAW SECTION ENDS

        // COURIER MANAGEMENT SECTION
        Route::get('/couriers/balances', [Operator\CourierManagementController::class, 'index'])->name('operator-courier-balances');
        Route::get('/courier/{id}/details', [Operator\CourierManagementController::class, 'show'])->name('operator-courier-details');
        Route::get('/courier/{id}/unsettled', [Operator\CourierManagementController::class, 'unsettledDeliveries'])->name('operator-courier-unsettled');
        // Use /accounts/couriers for courier accounting (AccountLedgerController)
        // COURIER MANAGEMENT SECTION ENDS

    });

    //------------ OPERATORMERCHANT SECTION ------------

    Route::group(['middleware' => 'permissions:vendors'], function () {

        Route::get('/merchants/datatables', [Operator\MerchantController::class, 'datatables'])->name('operator-merchant-datatables');
        Route::get('/merchants', [Operator\MerchantController::class, 'index'])->name('operator-merchant-index');

        Route::get('/merchants/{id}/show', [Operator\MerchantController::class, 'show'])->name('operator-merchant-show');
        Route::get('/merchants/{id}/items/datatables', [Operator\MerchantController::class, 'merchantItemsDatatables'])->name('operator-merchant-items-datatables');
        Route::get('/merchants/secret/login/{id}', [Operator\MerchantController::class, 'secretLogin'])->name('operator-merchant-secret');
        Route::get('/merchant/edit/{id}', [Operator\MerchantController::class, 'edit'])->name('operator-merchant-edit');
        Route::post('/merchant/edit/{id}', [Operator\MerchantController::class, 'update'])->name('operator-merchant-update');

        Route::get('/merchant/request-trust-badge/{id}', [Operator\MerchantController::class, 'requestTrustBadge'])->name('operator-merchant-request-trust-badge');
        Route::post('/merchant/request-trust-badge/{id}', [Operator\MerchantController::class, 'requestTrustBadgeSubmit'])->name('operator-merchant-request-trust-badge-submit');

        Route::get('/merchants/status/{id1}/{id2}', [Operator\MerchantController::class, 'status'])->name('operator-merchant-st');
        Route::delete('/merchants/delete/{id}', [Operator\MerchantController::class, 'destroy'])->name('operator-merchant-delete');

        Route::get('/merchants/withdraws/datatables', [Operator\MerchantController::class, 'withdrawdatatables'])->name('operator-merchant-withdraw-datatables'); //JSON REQUEST
        Route::get('/merchants/withdraws', [Operator\MerchantController::class, 'withdraws'])->name('operator-merchant-withdraw-index');
        Route::get('/merchants/withdraw/{id}/show', [Operator\MerchantController::class, 'withdrawdetails'])->name('operator-merchant-withdraw-show');
        Route::get('/merchants/withdraws/accept/{id}', [Operator\MerchantController::class, 'accept'])->name('operator-merchant-withdraw-accept');
        Route::get('/merchants/withdraws/reject/{id}', [Operator\MerchantController::class, 'reject'])->name('operator-merchant-withdraw-reject');
    });

    //------------ OPERATORMERCHANT SECTION ENDS ------------

    //------------ MERCHANT COMMISSION SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_membership_plans'], function () {
        Route::get('/merchant-commissions/datatables', [Operator\MerchantCommissionController::class, 'datatables'])->name('operator-merchant-commission-datatables');
        Route::get('/merchant-commissions', [Operator\MerchantCommissionController::class, 'index'])->name('operator-merchant-commission-index');
        Route::get('/merchant-commissions/edit/{id}', [Operator\MerchantCommissionController::class, 'edit'])->name('operator-merchant-commission-edit');
        Route::post('/merchant-commissions/update/{id}', [Operator\MerchantCommissionController::class, 'update'])->name('operator-merchant-commission-update');
        Route::post('/merchant-commissions/bulk-update', [Operator\MerchantCommissionController::class, 'bulkUpdate'])->name('operator-merchant-commission-bulk-update');
    });

    //------------ MERCHANT COMMISSION SECTION ENDS ------------

    //------------ MERCHANT TRUST BADGE SECTION ------------

    Route::group(['middleware' => 'permissions:vendor_verifications'], function () {

        Route::get('/trust-badges/datatables/{status}', [Operator\TrustBadgeController::class, 'datatables'])->name('operator-trust-badge-datatables');
        Route::get('/trust-badges/{slug}', [Operator\TrustBadgeController::class, 'index'])->name('operator-trust-badge-index');
        Route::get('/trust-badges/show/attachment', [Operator\TrustBadgeController::class, 'show'])->name('operator-trust-badge-show');
        Route::get('/trust-badges/edit/{id}', [Operator\TrustBadgeController::class, 'edit'])->name('operator-trust-badge-edit');
        Route::post('/trust-badges/edit/{id}', [Operator\TrustBadgeController::class, 'update'])->name('operator-trust-badge-update');
        Route::get('/trust-badges/status/{id1}/{id2}', [Operator\TrustBadgeController::class, 'status'])->name('operator-trust-badge-status');
        Route::delete('/trust-badges/delete/{id}', [Operator\TrustBadgeController::class, 'destroy'])->name('operator-trust-badge-delete');
    });

    //------------ MERCHANT TRUST BADGE SECTION ENDS ------------

    //------------ OPERATORSUPPORT TICKET SECTION ------------

    Route::group(['middleware' => 'permissions:messages'], function () {

        Route::get('/support-tickets/datatables/{type}', [Operator\SupportTicketController::class, 'datatables'])->name('operator-support-ticket-datatables');
        Route::get('/tickets', [Operator\SupportTicketController::class, 'index'])->name('operator-support-ticket-index');
        Route::get('/disputes', [Operator\SupportTicketController::class, 'dispute'])->name('operator-support-ticket-dispute');
        Route::get('/support-ticket/{id}', [Operator\SupportTicketController::class, 'message'])->name('operator-support-ticket-show');
        Route::get('/support-ticket/load/{id}', [Operator\SupportTicketController::class, 'messageshow'])->name('operator-support-ticket-load');
        Route::post('/support-ticket/post', [Operator\SupportTicketController::class, 'postmessage'])->name('operator-support-ticket-store');
        Route::delete('/support-ticket/{id}/delete', [Operator\SupportTicketController::class, 'messagedelete'])->name('operator-support-ticket-delete');
        Route::post('/user/send/support-ticket/admin', [Operator\SupportTicketController::class, 'usercontact'])->name('operator-send-support-ticket');
    });

    //------------ OPERATORSUPPORT TICKET SECTION ENDS ------------

    // PUBLICATION SECTION REMOVED - Feature deleted

    //------------ OPERATORGENERAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:muaadh_settings'], function () {

        Route::get('/general-settings/logo', [Operator\MuaadhSettingController::class, 'logo'])->name('operator-gs-logo');
        Route::get('/general-settings/favicon', [Operator\MuaadhSettingController::class, 'favicon'])->name('operator-gs-fav');
        Route::get('/general-settings/loader', [Operator\MuaadhSettingController::class, 'loader'])->name('operator-gs-load');
        Route::get('/general-settings/contents', [Operator\MuaadhSettingController::class, 'websitecontent'])->name('operator-gs-contents');
        Route::get('/general-settings/theme-colors', [Operator\MuaadhSettingController::class, 'themeColors'])->name('operator-theme-colors');
        Route::post('/general-settings/theme-colors/update', [Operator\MuaadhSettingController::class, 'updateThemeColors'])->name('operator-theme-colors-update');
        Route::get('/general-settings/affilate', [Operator\MuaadhSettingController::class, 'affilate'])->name('operator-gs-affilate');
        Route::get('/general-settings/error-banner', [Operator\MuaadhSettingController::class, 'error_banner'])->name('operator-gs-error-banner');
        Route::get('/general-settings/popup', [Operator\MuaadhSettingController::class, 'popup'])->name('operator-gs-popup');
        // Breadcrumb banner removed - using modern minimal design
        Route::get('/general-settings/maintenance', [Operator\MuaadhSettingController::class, 'maintain'])->name('operator-gs-maintenance');

        // Deal Of The Day

        //------------ OPERATORSHIPPING ------------

        Route::get('/shipping/datatables', [Operator\ShippingController::class, 'datatables'])->name('operator-shipping-datatables');
        Route::get('/shipping', [Operator\ShippingController::class, 'index'])->name('operator-shipping-index');
        Route::get('/shipping/create', [Operator\ShippingController::class, 'create'])->name('operator-shipping-create');
        Route::post('/shipping/create', [Operator\ShippingController::class, 'store'])->name('operator-shipping-store');
        Route::get('/shipping/edit/{id}', [Operator\ShippingController::class, 'edit'])->name('operator-shipping-edit');
        Route::post('/shipping/edit/{id}', [Operator\ShippingController::class, 'update'])->name('operator-shipping-update');
        Route::delete('/shipping/delete/{id}', [Operator\ShippingController::class, 'destroy'])->name('operator-shipping-delete');

        //------------ OPERATORSHIPPING ENDS ------------

    });

    //------------ OPERATORGENERAL SETTINGS SECTION ENDS ------------

    //------------ OPERATORHOME PAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:home_page_settings'], function () {

        Route::get('/home-page-settings', [Operator\MuaadhSettingController::class, 'homepage'])->name('operator-home-page-index');


        //------------ OPERATORHOME PAGE THEMES SECTION ------------

        Route::get('/home-themes', [Operator\HomePageThemeController::class, 'index'])->name('operator-homethemes-index');
        Route::get('/home-themes/create', [Operator\HomePageThemeController::class, 'create'])->name('operator-homethemes-create');
        Route::post('/home-themes/store', [Operator\HomePageThemeController::class, 'store'])->name('operator-homethemes-store');
        Route::get('/home-themes/edit/{id}', [Operator\HomePageThemeController::class, 'edit'])->name('operator-homethemes-edit');
        Route::put('/home-themes/update/{id}', [Operator\HomePageThemeController::class, 'update'])->name('operator-homethemes-update');
        Route::get('/home-themes/activate/{id}', [Operator\HomePageThemeController::class, 'activate'])->name('operator-homethemes-activate');
        Route::get('/home-themes/duplicate/{id}', [Operator\HomePageThemeController::class, 'duplicate'])->name('operator-homethemes-duplicate');
        Route::delete('/home-themes/delete/{id}', [Operator\HomePageThemeController::class, 'destroy'])->name('operator-homethemes-delete');

        //------------ OPERATORHOME PAGE THEMES SECTION ENDS ------------

        // FEATURED PROMO SECTION REMOVED - Feature deleted
        // ANNOUNCEMENT SECTION REMOVED - Feature deleted

        //------------ OPERATORBRAND SECTION ------------

        Route::get('/brand/datatables', [Operator\BrandController::class, 'datatables'])->name('operator-brand-datatables');
        Route::get('/brand', [Operator\BrandController::class, 'index'])->name('operator-brand-index');
        Route::get('/brand/create', [Operator\BrandController::class, 'create'])->name('operator-brand-create');
        Route::post('/brand/create', [Operator\BrandController::class, 'store'])->name('operator-brand-store');
        Route::get('/brand/edit/{id}', [Operator\BrandController::class, 'edit'])->name('operator-brand-edit');
        Route::post('/brand/edit/{id}', [Operator\BrandController::class, 'update'])->name('operator-brand-update');
        Route::delete('/brand/delete/{id}', [Operator\BrandController::class, 'destroy'])->name('operator-brand-delete');

        //------------ OPERATORBRAND SECTION ENDS ------------

        //------------ OPERATOR ALTERNATIVES SECTION ------------

        Route::get('/alternatives', [Operator\AlternativeController::class, 'index'])->name('operator-alternative-index');
        Route::get('/alternatives/search', [Operator\AlternativeController::class, 'search'])->name('operator-alternative-search');
        Route::get('/alternatives/stats', [Operator\AlternativeController::class, 'stats'])->name('operator-alternative-stats');
        Route::post('/alternatives/add', [Operator\AlternativeController::class, 'addAlternative'])->name('operator-alternative-add');
        Route::post('/alternatives/remove', [Operator\AlternativeController::class, 'removeAlternative'])->name('operator-alternative-remove');

        //------------ OPERATOR ALTERNATIVES SECTION ENDS ------------

    });

    //------------ OPERATORHOME PAGE SETTINGS SECTION ENDS ------------

    Route::group(['middleware' => 'permissions:menu_page_settings'], function () {
        // HELP ARTICLE SECTION REMOVED - Feature deleted
        // STATIC CONTENT SECTION REMOVED - Use pages table for policies

        Route::get('/frontend-setting/contact', [Operator\FrontendSettingController::class, 'contact'])->name('operator-fs-contact');
        Route::post('/frontend-setting/update/all', [Operator\FrontendSettingController::class, 'update'])->name('operator-fs-update');
    });

    //------------ OPERATOREMAIL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:email_settings'], function () {

        Route::get('/comms-blueprints/datatables', [Operator\CommsBlueprintController::class, 'datatables'])->name('operator-mail-datatables');
        Route::get('/comms-blueprints', [Operator\CommsBlueprintController::class, 'index'])->name('operator-mail-index');
        Route::get('/comms-blueprints/{id}', [Operator\CommsBlueprintController::class, 'edit'])->name('operator-mail-edit');
        Route::post('/comms-blueprints/{id}', [Operator\CommsBlueprintController::class, 'update'])->name('operator-mail-update');
        Route::get('/email-config', [Operator\CommsBlueprintController::class, 'config'])->name('operator-mail-config');
        Route::get('/groupemail', [Operator\CommsBlueprintController::class, 'groupemail'])->name('operator-group-show');
        Route::post('/groupemailpost', [Operator\CommsBlueprintController::class, 'groupemailpost'])->name('operator-group-submit');
    });

    if(module("otp")){
        
    Route::group(['middleware' => 'permissions:otp_setting'], function () {
        Route::get('/opt/config', [Operator\MuaadhSettingController::class, 'otpConfig'])->name('operator-otp-config');
    });

    }

    //------------ OPERATOREMAIL SETTINGS SECTION ENDS ------------

    //------------ OPERATORPAYMENT SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:payment_settings'], function () {

        // Payment Informations

        Route::get('/payment-informations', [Operator\MuaadhSettingController::class, 'paymentsinfo'])->name('operator-gs-payments');

        // Merchant Payments

        Route::get('/merchant-payment/datatables', [Operator\MerchantPaymentController::class, 'datatables'])->name('operator-merchant-payment-datatables'); //JSON REQUEST
        Route::get('/merchant-payment', [Operator\MerchantPaymentController::class, 'index'])->name('operator-merchant-payment-index');
        Route::get('/merchant-payment/create', [Operator\MerchantPaymentController::class, 'create'])->name('operator-merchant-payment-create');
        Route::post('/merchant-payment/create', [Operator\MerchantPaymentController::class, 'store'])->name('operator-merchant-payment-store');
        Route::get('/merchant-payment/edit/{id}', [Operator\MerchantPaymentController::class, 'edit'])->name('operator-merchant-payment-edit');
        Route::post('/merchant-payment/update/{id}', [Operator\MerchantPaymentController::class, 'update'])->name('operator-merchant-payment-update');
        Route::delete('/merchant-payment/delete/{id}', [Operator\MerchantPaymentController::class, 'destroy'])->name('operator-merchant-payment-delete');
        Route::get('/merchant-payment/status/{field}/{id1}/{id2}', [Operator\MerchantPaymentController::class, 'status'])->name('operator-merchant-payment-status');

        // Monetary Unit Settings

        // MULTIPLE MONETARY UNITS

        Route::get('/monetary-unit/datatables', [Operator\MonetaryUnitController::class, 'datatables'])->name('operator-monetary-unit-datatables'); //JSON REQUEST
        Route::get('/monetary-unit', [Operator\MonetaryUnitController::class, 'index'])->name('operator-monetary-unit-index');
        Route::get('/monetary-unit/create', [Operator\MonetaryUnitController::class, 'create'])->name('operator-monetary-unit-create');
        Route::post('/monetary-unit/create', [Operator\MonetaryUnitController::class, 'store'])->name('operator-monetary-unit-store');
        Route::get('/monetary-unit/edit/{id}', [Operator\MonetaryUnitController::class, 'edit'])->name('operator-monetary-unit-edit');
        Route::post('/monetary-unit/update/{id}', [Operator\MonetaryUnitController::class, 'update'])->name('operator-monetary-unit-update');
        Route::delete('/monetary-unit/delete/{id}', [Operator\MonetaryUnitController::class, 'destroy'])->name('operator-monetary-unit-delete');
        Route::get('/monetary-unit/status/{id1}/{id2}', [Operator\MonetaryUnitController::class, 'status'])->name('operator-monetary-unit-status');

    });

    //------------ OPERATORPAYMENT SETTINGS SECTION ENDS------------

    //------------ OPERATORSOCIAL SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:social_settings'], function () {

        //------------ OPERATOR NETWORK PRESENCE ------------

        Route::get('/network-presence/datatables', [Operator\NetworkPresenceController::class, 'datatables'])->name('operator-network-presence-datatables'); //JSON REQUEST
        Route::get('/network-presence', [Operator\NetworkPresenceController::class, 'index'])->name('operator-network-presence-index');
        Route::get('/network-presence/create', [Operator\NetworkPresenceController::class, 'create'])->name('operator-network-presence-create');
        Route::post('/network-presence/create', [Operator\NetworkPresenceController::class, 'store'])->name('operator-network-presence-store');
        Route::get('/network-presence/edit/{id}', [Operator\NetworkPresenceController::class, 'edit'])->name('operator-network-presence-edit');
        Route::post('/network-presence/edit/{id}', [Operator\NetworkPresenceController::class, 'update'])->name('operator-network-presence-update');
        Route::delete('/network-presence/delete/{id}', [Operator\NetworkPresenceController::class, 'destroy'])->name('operator-network-presence-delete');
        Route::get('/network-presence/status/{id1}/{id2}', [Operator\NetworkPresenceController::class, 'status'])->name('operator-network-presence-status');

        //------------ OPERATOR NETWORK PRESENCE ENDS ------------

        // CONNECT CONFIG SECTION REMOVED - OAuth settings now in platform_settings
    });
    //------------ OPERATOR CONNECT CONFIG SECTION ENDS------------

    //------------ OPERATORLANGUAGE SETTINGS SECTION ------------

    Route::group(['middleware' => 'permissions:language_settings'], function () {

        //  Multiple Language Section

        //  Multiple Language Section Ends

        Route::get('/languages/datatables', [Operator\LanguageController::class, 'datatables'])->name('operator-lang-datatables'); //JSON REQUEST
        Route::get('/languages', [Operator\LanguageController::class, 'index'])->name('operator-lang-index');
        Route::get('/languages/create', [Operator\LanguageController::class, 'create'])->name('operator-lang-create');
        Route::get('/languages/import', [Operator\LanguageController::class, 'import'])->name('operator-lang-import');
        Route::get('/languages/edit/{id}', [Operator\LanguageController::class, 'edit'])->name('operator-lang-edit');
        Route::get('/languages/export/{id}', [Operator\LanguageController::class, 'export'])->name('operator-lang-export');
        Route::post('/languages/create', [Operator\LanguageController::class, 'store'])->name('operator-lang-store');
        Route::post('/languages/import/create', [Operator\LanguageController::class, 'importStore'])->name('operator-lang-import-store');
        Route::post('/languages/edit/{id}', [Operator\LanguageController::class, 'update'])->name('operator-lang-update');
        Route::get('/languages/status/{id1}/{id2}', [Operator\LanguageController::class, 'status'])->name('operator-lang-st');
        Route::delete('/languages/delete/{id}', [Operator\LanguageController::class, 'destroy'])->name('operator-lang-delete');


        //------------ OPERATORLANGUAGE SETTINGS SECTION ENDS ------------

    });

    // TYPEFACE SECTION REMOVED - No custom fonts feature
    // SEOTOOL SECTION REMOVED - SEO now in platform_settings

    //------------ OPERATORSTAFF SECTION ------------

    Route::group(['middleware' => 'permissions:manage_staffs'], function () {

        Route::get('/staff/datatables', [Operator\StaffController::class, 'datatables'])->name('operator-staff-datatables');
        Route::get('/staff', [Operator\StaffController::class, 'index'])->name('operator-staff-index');
        Route::get('/staff/create', [Operator\StaffController::class, 'create'])->name('operator-staff-create');
        Route::post('/staff/create', [Operator\StaffController::class, 'store'])->name('operator-staff-store');
        Route::get('/staff/edit/{id}', [Operator\StaffController::class, 'edit'])->name('operator-staff-edit');
        Route::post('/staff/update/{id}', [Operator\StaffController::class, 'update'])->name('operator-staff-update');
        Route::get('/staff/show/{id}', [Operator\StaffController::class, 'show'])->name('operator-staff-show');
        Route::delete('/staff/delete/{id}', [Operator\StaffController::class, 'destroy'])->name('operator-staff-delete');
    });

    //------------ OPERATORSTAFF SECTION ENDS------------

    //------------ OPERATORMAILING LIST SECTION ------------

    Route::group(['middleware' => 'permissions:subscribers'], function () {

        Route::get('/mailing-list/datatables', [Operator\MailingListController::class, 'datatables'])->name('operator-mailing-list-datatables'); //JSON REQUEST
        Route::get('/mailing-list', [Operator\MailingListController::class, 'index'])->name('operator-mailing-list-index');
        Route::get('/mailing-list/download', [Operator\MailingListController::class, 'download'])->name('operator-mailing-list-download');
    });

    //------------ OPERATORMAILING LIST ENDS ------------

    // ------------ GLOBAL ----------------------
    Route::post('/general-settings/update/all', [Operator\MuaadhSettingController::class, 'generalupdate'])->name('operator-gs-update');
    Route::post('/general-settings/update/theme', [Operator\MuaadhSettingController::class, 'updateTheme'])->name('operator-gs-update-theme');
    Route::post('/general-settings/update/payment', [Operator\MuaadhSettingController::class, 'generalupdatepayment'])->name('operator-gs-update-payment');
    Route::post('/general-settings/update/mail', [Operator\MuaadhSettingController::class, 'generalMailUpdate'])->name('operator-gs-update-mail');
    Route::get('/general-settings/status/{field}/{status}', [Operator\MuaadhSettingController::class, 'status'])->name('operator-gs-status');

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

        Route::get('/admin-role/datatables', [Operator\RoleController::class, 'datatables'])->name('operator-role-datatables');
        Route::get('/admin-role', [Operator\RoleController::class, 'index'])->name('operator-role-index');
        Route::get('/admin-role/create', [Operator\RoleController::class, 'create'])->name('operator-role-create');
        Route::post('/admin-role/create', [Operator\RoleController::class, 'store'])->name('operator-role-store');
        Route::get('/admin-role/edit/{id}', [Operator\RoleController::class, 'edit'])->name('operator-role-edit');
        Route::post('/admin-role/edit/{id}', [Operator\RoleController::class, 'update'])->name('operator-role-update');
        Route::delete('/admin-role/delete/{id}', [Operator\RoleController::class, 'destroy'])->name('operator-role-delete');

        // ------------ ADMIN ROLE SECTION ENDS ----------------------

        // ------------ MODULE SECTION ----------------------

        Route::get('/module/datatables', [Operator\ModuleController::class, 'datatables'])->name('operator-module-datatables');
        Route::get('/module', [Operator\ModuleController::class, 'index'])->name('operator-module-index');
        Route::get('/module/create', [Operator\ModuleController::class, 'create'])->name('operator-module-create');
        Route::post('/module/install', [Operator\ModuleController::class, 'install'])->name('operator-module-install');
        Route::get('/module/uninstall/{id}', [Operator\ModuleController::class, 'uninstall'])->name('operator-module-uninstall');

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
            Route::get('/dashboard', [Merchant\MerchantController::class, 'index'])->name('merchant.dashboard');

            // TRUST BADGE - يمكن لجميع التجار رفع مستندات التوثيق
            Route::get('/trust-badge', [Merchant\MerchantController::class, 'trustBadge'])->name('merchant-trust-badge');
            Route::get('/warning/trust-badge/{id}', [Merchant\MerchantController::class, 'warningTrustBadge'])->name('merchant-warning');
            Route::post('/trust-badge', [Merchant\MerchantController::class, 'trustBadgeSubmit'])->name('merchant-trust-badge-submit');

            // PROFILE - يمكن لجميع التجار رؤية وتعديل بروفايلهم
            Route::get('/profile', [Merchant\MerchantController::class, 'profile'])->name('merchant-profile');
            Route::post('/profile', [Merchant\MerchantController::class, 'profileupdate'])->name('merchant-profile-update');

            // MERCHANT LOGO - شعار التاجر للفواتير
            Route::get('/logo', [Merchant\MerchantController::class, 'logo'])->name('merchant-logo');
            Route::post('/logo', [Merchant\MerchantController::class, 'logoUpdate'])->name('merchant-logo-update');
            Route::delete('/logo', [Merchant\MerchantController::class, 'logoDelete'])->name('merchant-logo-delete');
        });

        // ============ TRUSTED MERCHANT ONLY ============
        // هذه المسارات متاحة فقط للتجار الموثقين (is_merchant = 2)
        Route::group(['middleware' => ['merchant', 'trusted.merchant']], function () {

            //------------ PURCHASE SECTION ------------

            Route::get('/purchases/datatables', [Merchant\PurchaseController::class, 'datatables'])->name('merchant-purchase-datatables');
            Route::get('/purchases', [Merchant\PurchaseController::class, 'index'])->name('merchant-purchase-index');
            Route::get('/purchase/{id}/show', [Merchant\PurchaseController::class, 'show'])->name('merchant-purchase-show');
            Route::get('/purchase/{id}/invoice', [Merchant\PurchaseController::class, 'invoice'])->name('merchant-purchase-invoice');
            Route::get('/purchase/{id}/print', [Merchant\PurchaseController::class, 'printpage'])->name('merchant-purchase-print');
            Route::get('/purchase/{id1}/status/{status}', [Merchant\PurchaseController::class, 'status'])->name('merchant-purchase-status');
            Route::post('/purchase/email/', [Merchant\PurchaseController::class, 'emailsub'])->name('merchant-purchase-emailsub');

            //------------ PURCHASE SECTION ENDS------------

            Route::get('delivery/datatables', [Merchant\DeliveryController::class, 'datatables'])->name('merchant-delivery-purchase-datatables');
            Route::get('delivery', [Merchant\DeliveryController::class, 'index'])->name('merchant.delivery.index');
            Route::get('delivery/boy/find', [Merchant\DeliveryController::class, 'findCourier'])->name('merchant.find.courier');
            Route::post('courier/search/submit', [Merchant\DeliveryController::class, 'findCourierSubmit'])->name('merchant-courier-search-submit');

            // Tryoto Shipping Routes
            Route::get('delivery/shipping-options', [Merchant\DeliveryController::class, 'getShippingOptions'])->name('merchant.shipping.options');
            Route::post('delivery/send-to-tryoto', [Merchant\DeliveryController::class, 'sendToTryoto'])->name('merchant.send.tryoto');
            Route::get('delivery/track-shipment', [Merchant\DeliveryController::class, 'trackShipment'])->name('merchant.track.shipment');
            Route::get('delivery/shipment-history/{purchaseId}', [Merchant\DeliveryController::class, 'shipmentHistory'])->name('merchant.shipment.history');
            Route::post('delivery/cancel-shipment', [Merchant\DeliveryController::class, 'cancelShipment'])->name('merchant.cancel.shipment');
            Route::post('delivery/ready-for-courier', [Merchant\DeliveryController::class, 'markReadyForCourierCollection'])->name('merchant.ready.courier');
            Route::post('delivery/handover-to-courier', [Merchant\DeliveryController::class, 'confirmHandoverToCourier'])->name('merchant.handover.courier');
            Route::get('delivery/stats', [Merchant\DeliveryController::class, 'shippingStats'])->name('merchant.shipping.stats');
            Route::get('delivery/purchase-status/{purchaseId}', [Merchant\DeliveryController::class, 'getPurchaseShipmentStatus'])->name('merchant.purchase.shipment.status');

            // Dynamic Shipping Provider Routes
            Route::get('delivery/shipping-providers', [Merchant\DeliveryController::class, 'getShippingProviders'])->name('merchant.shipping.providers');
            Route::get('delivery/provider-options', [Merchant\DeliveryController::class, 'getProviderShippingOptions'])->name('merchant.provider.shipping.options');
            Route::post('delivery/send-provider-shipping', [Merchant\DeliveryController::class, 'sendProviderShipping'])->name('merchant.send.provider.shipping');
            Route::get('delivery/couriers', [Merchant\DeliveryController::class, 'findCourier'])->name('merchant.delivery.couriers');
            Route::get('delivery/merchant-branches', [Merchant\DeliveryController::class, 'getMerchantBranches'])->name('merchant.delivery.branches');

            //------------ MERCHANT CATALOG ITEM SECTION ------------

            Route::get('/catalog-items/datatables', [Merchant\CatalogItemController::class, 'datatables'])->name('merchant-catalog-item-datatables');
            Route::get('/catalog-items', [Merchant\CatalogItemController::class, 'index'])->name('merchant-catalog-item-index');

            // CREATE SECTION
            Route::get('/catalog-items/search-item', [Merchant\CatalogItemController::class, 'searchItem'])->name('merchant-catalog-item-search-item');
            Route::get('/catalog-items/{slug}/create', [Merchant\CatalogItemController::class, 'create'])->name('merchant-catalog-item-create');
            Route::post('/catalog-items/store', [Merchant\CatalogItemController::class, 'store'])->name('merchant-catalog-item-store');

            // EDIT SECTION
            Route::get('/catalog-items/edit/{merchantItemId}', [Merchant\CatalogItemController::class, 'edit'])->name('merchant-catalog-item-edit');
            Route::post('/catalog-items/edit/{merchantItemId}', [Merchant\CatalogItemController::class, 'update'])->name('merchant-catalog-item-update');

            // STATUS SECTION
            Route::get('/catalog-items/status/{id1}/{id2}', [Merchant\CatalogItemController::class, 'status'])->name('merchant-catalog-item-status');

            // DELETE SECTION
            Route::delete('/catalog-items/delete/{id}', [Merchant\CatalogItemController::class, 'destroy'])->name('merchant-catalog-item-delete');

            //------------ MERCHANT CATALOG ITEM SECTION ENDS------------

            //------------ STOCK MANAGEMENT SECTION (Merchant #1 only) ------------
            Route::get('/stock/management', [Merchant\StockManagementController::class, 'index'])->name('merchant-stock-management');
            Route::get('/stock/datatables', [Merchant\StockManagementController::class, 'datatables'])->name('merchant-stock-datatables');
            Route::get('/stock/export', [Merchant\StockManagementController::class, 'export'])->name('merchant-stock-export');
            Route::get('/stock/download/{id}', [Merchant\StockManagementController::class, 'download'])->name('merchant-stock-download');
            Route::post('/stock/full-refresh', [Merchant\StockManagementController::class, 'triggerFullRefresh'])->name('merchant-stock-full-refresh');
            Route::post('/stock/process-full-refresh', [Merchant\StockManagementController::class, 'processFullRefresh'])->name('merchant-stock-process-full-refresh');
            Route::get('/stock/progress/{id}', [Merchant\StockManagementController::class, 'getUpdateProgress'])->name('merchant-stock-progress');
            //------------ STOCK MANAGEMENT SECTION ENDS ------------

            //------------ MERCHANT MY ITEM IMAGES SECTION ------------
            Route::get('/my-items/images', [Merchant\MyItemImageController::class, 'index'])->name('merchant-my-item-images');
            Route::get('/my-items/images/datatables', [Merchant\MyItemImageController::class, 'datatables'])->name('merchant-my-item-images-datatables');
            Route::get('/my-items/images/{id}', [Merchant\MyItemImageController::class, 'show'])->name('merchant-my-item-images-show');
            Route::post('/my-items/images', [Merchant\MyItemImageController::class, 'store'])->name('merchant-my-item-images-store');
            Route::post('/my-items/images/{id}', [Merchant\MyItemImageController::class, 'update'])->name('merchant-my-item-images-update');
            Route::delete('/my-items/images/{id}', [Merchant\MyItemImageController::class, 'destroy'])->name('merchant-my-item-images-delete');
            //------------ MERCHANT MY ITEM IMAGES SECTION ENDS------------

            //------------ MERCHANT SHIPPING ------------

            Route::get('/shipping/datatables', [Merchant\ShippingController::class, 'datatables'])->name('merchant-shipping-datatables');
            Route::get('/shipping', [Merchant\ShippingController::class, 'index'])->name('merchant-shipping-index');
            Route::get('/shipping/create', [Merchant\ShippingController::class, 'create'])->name('merchant-shipping-create');
            Route::post('/shipping/create', [Merchant\ShippingController::class, 'store'])->name('merchant-shipping-store');
            Route::get('/shipping/edit/{id}', [Merchant\ShippingController::class, 'edit'])->name('merchant-shipping-edit');
            Route::post('/shipping/edit/{id}', [Merchant\ShippingController::class, 'update'])->name('merchant-shipping-update');
            Route::delete('/shipping/delete/{id}', [Merchant\ShippingController::class, 'destroy'])->name('merchant-shipping-delete');

            //------------ MERCHANT SHIPPING ENDS ------------

            //------------ MERCHANT CATALOG EVENT SECTION ------------

            Route::get('/purchase/event/show/{id}', [Merchant\CatalogEventController::class, 'showPurchaseEvents'])->name('merchant-purchase-event-show');
            Route::get('/purchase/event/count/{id}', [Merchant\CatalogEventController::class, 'countPurchaseEvents'])->name('merchant-purchase-event-count');
            Route::get('/purchase/event/clear/{id}', [Merchant\CatalogEventController::class, 'clearPurchaseEvents'])->name('merchant-purchase-event-clear');

            //------------ MERCHANT CATALOG EVENT SECTION ENDS ------------

            Route::get('/withdraw/datatables', [Merchant\WithdrawController::class, 'datatables'])->name('merchant-wt-datatables');
            Route::get('/withdraw', [Merchant\WithdrawController::class, 'index'])->name('merchant-wt-index');
            Route::get('/withdraw/create', [Merchant\WithdrawController::class, 'create'])->name('merchant-wt-create');
            Route::post('/withdraw/create', [Merchant\WithdrawController::class, 'store'])->name('merchant-wt-store');

            //------------ MERCHANT BRANCH (Warehouse/Origin) ------------
            Route::get('/branch/datatables', [Merchant\MerchantBranchController::class, 'datatables'])->name('merchant-branch-datatables');
            Route::get('/branch', [Merchant\MerchantBranchController::class, 'index'])->name('merchant-branch-index');
            Route::get('/branch/create', [Merchant\MerchantBranchController::class, 'create'])->name('merchant-branch-create');
            Route::post('/branch/create', [Merchant\MerchantBranchController::class, 'store'])->name('merchant-branch-store');
            Route::get('/branch/edit/{id}', [Merchant\MerchantBranchController::class, 'edit'])->name('merchant-branch-edit');
            Route::post('/branch/edit/{id}', [Merchant\MerchantBranchController::class, 'update'])->name('merchant-branch-update');
            Route::get('/branch/delete/{id}', [Merchant\MerchantBranchController::class, 'destroy'])->name('merchant-branch-delete');
            Route::get('/branch/status/{id}/{status}', [Merchant\MerchantBranchController::class, 'status'])->name('merchant-branch-status');
            Route::get('/branch/cities', [Merchant\MerchantBranchController::class, 'getCitiesByCountry'])->name('merchant-branch-get-cities');

            //------------ MERCHANT BRANCH END ------------

            //------------ MERCHANT NETWORK PRESENCE ------------

            Route::get('/network-presence/datatables', [Merchant\NetworkPresenceController::class, 'datatables'])->name('merchant-network-presence-datatables'); //JSON REQUEST
            Route::get('/network-presence', [Merchant\NetworkPresenceController::class, 'index'])->name('merchant-network-presence-index');
            Route::get('/network-presence/create', [Merchant\NetworkPresenceController::class, 'create'])->name('merchant-network-presence-create');
            Route::post('/network-presence/create', [Merchant\NetworkPresenceController::class, 'store'])->name('merchant-network-presence-store');
            Route::get('/network-presence/edit/{id}', [Merchant\NetworkPresenceController::class, 'edit'])->name('merchant-network-presence-edit');
            Route::post('/network-presence/edit/{id}', [Merchant\NetworkPresenceController::class, 'update'])->name('merchant-network-presence-update');
            Route::delete('/network-presence/delete/{id}', [Merchant\NetworkPresenceController::class, 'destroy'])->name('merchant-network-presence-delete');
            Route::get('/network-presence/status/{id1}/{id2}', [Merchant\NetworkPresenceController::class, 'status'])->name('merchant-network-presence-status');

            //------------ MERCHANT NETWORK PRESENCE ENDS ------------

            //------------ MERCHANT SHIPMENT TRACKING (NEW SYSTEM) ------------

            Route::get('/shipment-tracking', [Merchant\ShipmentTrackingController::class, 'index'])->name('merchant.shipment-tracking.index');
            Route::get('/shipment-tracking/{purchaseId}', [Merchant\ShipmentTrackingController::class, 'show'])->name('merchant.shipment-tracking.show');
            Route::put('/shipment-tracking/{purchaseId}', [Merchant\ShipmentTrackingController::class, 'updateStatus'])->name('merchant.shipment-tracking.update');
            Route::post('/shipment-tracking/{purchaseId}/start', [Merchant\ShipmentTrackingController::class, 'startManualShipment'])->name('merchant.shipment-tracking.start');
            Route::get('/shipment-tracking/{purchaseId}/refresh', [Merchant\ShipmentTrackingController::class, 'refreshFromApi'])->name('merchant.shipment-tracking.refresh');
            Route::get('/shipment-tracking/{purchaseId}/history', [Merchant\ShipmentTrackingController::class, 'getHistory'])->name('merchant.shipment-tracking.history');
            Route::get('/shipment-tracking-stats', [Merchant\ShipmentTrackingController::class, 'stats'])->name('merchant.shipment-tracking.stats');

            //------------ MERCHANT SHIPMENT TRACKING ENDS ------------

            //------------ MERCHANT CREDENTIALS SECTION - REMOVED ------------
            // Credentials are managed by OPERATOR only (not merchants)
            // See: operator/merchant-credentials routes instead
            //------------ MERCHANT CREDENTIALS SECTION ENDS------------

            // -------------------------- Merchant Income ------------------------------------//
            Route::get('earning/datatables', [Merchant\IncomeController::class, 'datatables'])->name('merchant.income.datatables');
            Route::get('total/earning', [Merchant\IncomeController::class, 'index'])->name('merchant.income');
            Route::get('tax-report', [Merchant\IncomeController::class, 'taxReport'])->name('merchant.tax-report');
            Route::get('statement', [Merchant\IncomeController::class, 'statement'])->name('merchant.statement');
            Route::get('statement/pdf', [Merchant\IncomeController::class, 'statementPdf'])->name('merchant.statement.pdf');
            Route::get('monthly-ledger', [Merchant\IncomeController::class, 'monthlyLedger'])->name('merchant.monthly-ledger');
            Route::get('monthly-ledger/pdf', [Merchant\IncomeController::class, 'monthlyLedgerPdf'])->name('merchant.monthly-ledger.pdf');
            Route::get('payouts', [Merchant\IncomeController::class, 'payouts'])->name('merchant.payouts');

        });
    });

    // ************************************ MERCHANT SECTION ENDS**********************************************

    // ************************************ USER SECTION **********************************************

    Route::get('user/success/{status}', function ($status) {
        return view('user.success', compact('status'));
    })->name('user.success');

    Route::prefix('user')->group(function () {
        
        

        // USER AUTH SECION
        Route::get('/login', [User\LoginController::class, 'showLoginForm'])->name('user.login');
        Route::get('/login/with/otp', [User\LoginController::class, 'showOtpLoginForm'])->name('user.otp.login');
        Route::post('/login/with/otp/submit', [User\LoginController::class, 'showOtpLoginFormSubmit'])->name('user.opt.login.submit');
        Route::get('/login/with/otp/view', [User\LoginController::class, 'showOtpLoginFormView'])->name('user.opt.login.view');
        Route::post('/login/with/otp/view/submit', [User\LoginController::class, 'showOtpLoginFormViewSubmit'])->name('user.opt.login.view.submit');
        Route::get('/merchant-login', [User\LoginController::class, 'showMerchantLoginForm'])->name('merchant.login');

        Route::get('/register', [User\RegisterController::class, 'showRegisterForm'])->name('user.register');
        Route::get('/merchant-register', [User\RegisterController::class, 'showMerchantRegisterForm'])->name('merchant.register');
        // User Login
        Route::post('/login', [Auth\User\LoginController::class, 'login'])->name('user.login.submit');
        // User Login End

        // User Register
        Route::post('/register', [Auth\User\RegisterController::class, 'register'])->name('user-register-submit');
        Route::get('/register/verify/{token}', [Auth\User\RegisterController::class, 'token'])->name('user-register-token');
        // User Register End

        //------------ USER FORGOT SECTION ------------
        Route::get('/forgot', [Auth\User\ForgotController::class, 'index'])->name('user.forgot');
        Route::post('/forgot', [Auth\User\ForgotController::class, 'forgot'])->name('user.forgot.submit');
        Route::get('/change-password/{token}', [Auth\User\ForgotController::class, 'showChangePassForm'])->name('user.change.token');
        Route::post('/change-password', [Auth\User\ForgotController::class, 'changepass'])->name('user.change.password');

        //------------ USER FORGOT SECTION ENDS ------------

        Route::get('/logout', [User\LoginController::class, 'logout'])->name('user-logout');
        Route::get('/dashboard', [User\UserController::class, 'index'])->name('user-dashboard');

        // Merchant Application (for regular users to become merchants)
        Route::get('/apply-merchant', [User\UserController::class, 'applyMerchant'])->name('user.apply-merchant');
        Route::post('/apply-merchant', [User\UserController::class, 'submitMerchantApplication'])->name('user.apply-merchant-submit');

        // User Reset
        Route::get('/reset', [User\UserController::class, 'resetform'])->name('user-reset');
        Route::post('/reset', [User\UserController::class, 'reset'])->name('user-reset-submit');
        // User Reset End

        // User Profile
        Route::get('/profile', [User\UserController::class, 'profile'])->name('user-profile');
        Route::post('/profile', [User\UserController::class, 'profileupdate'])->name('user-profile-update');
        // User Profile Ends

        // Get cities by country (states removed) - moved to GeocodingController
        Route::get('/country/wise/city/{country_id}', [\App\Http\Controllers\Api\GeocodingController::class, 'getCitiesByCountry'])->name('country.wise.city');
        Route::get('/user/country/wise/city', [\App\Http\Controllers\Api\GeocodingController::class, 'getCitiesByCountry'])->name('country.wise.city.user');

        // User Favorites
        Route::get('/favorites', [User\FavoriteController::class, 'favorites'])->name('user-favorites');

        Route::get('/favorite/add/merchant/{merchantItemId}', [User\FavoriteController::class, 'add'])->name('user-favorite-add-merchant');
        Route::get('/favorite/remove/{id}', [User\FavoriteController::class, 'remove'])->name('user-favorite-remove');
        // User Favorites Ends

        // User Purchases

        Route::get('/purchases', [User\PurchaseController::class, 'purchases'])->name('user-purchases');
        Route::get('/purchase/tracking', [User\PurchaseController::class, 'purchasetrack'])->name('user-purchase-track');
        Route::get('/purchase/trackings/{id}', [User\PurchaseController::class, 'trackload'])->name('user-purchase-track-search');
        Route::get('/purchase/{id}', [User\PurchaseController::class, 'purchase'])->name('user-purchase');
        Route::get('/download/purchase/{slug}/{id}', [User\PurchaseController::class, 'purchasedownload'])->name('user-purchase-download');
        Route::get('print/purchase/print/{id}', [User\PurchaseController::class, 'purchaseprint'])->name('user-purchase-print');
        Route::get('/json/trans', [User\PurchaseController::class, 'trans']);
        Route::post('/purchase/{id}/confirm-delivery', [User\PurchaseController::class, 'confirmDeliveryReceipt'])->name('user-confirm-delivery');

        // User Purchases Ends

        // User Merchant Chat

        Route::post('/user/contact', [User\ChatController::class, 'usercontact'])->name('user-contact');
        Route::get('/chats', [User\ChatController::class, 'messages'])->name('user-chats');
        Route::get('/chat/{id}', [User\ChatController::class, 'message'])->name('user-chat');
        Route::post('/chat/post', [User\ChatController::class, 'postmessage'])->name('user-chat-post');
        Route::get('/chat/{id}/delete', [User\ChatController::class, 'messagedelete'])->name('user-chat-delete');
        Route::get('/chat/load/{id}', [User\ChatController::class, 'msgload'])->name('user-chat-load');

        // User Merchant Chat Ends

        // User Support Tickets

        // Tickets
        Route::get('admin/tickets', [User\ChatController::class, 'adminmessages'])->name('user-ticket-index');
        // Disputes
        Route::get('admin/disputes', [User\ChatController::class, 'adminDiscordmessages'])->name('user-dispute-index');

        Route::get('admin/ticket/{id}', [User\ChatController::class, 'adminmessage'])->name('user-ticket-show');
        Route::post('admin/ticket/post', [User\ChatController::class, 'adminpostmessage'])->name('user-ticket-store');
        Route::get('admin/ticket/{id}/delete', [User\ChatController::class, 'adminmessagedelete'])->name('user-ticket-delete');
        Route::post('admin/user/send/ticket', [User\ChatController::class, 'adminusercontact'])->name('user-send-ticket');
        Route::get('admin/ticket/load/{id}', [User\ChatController::class, 'messageload'])->name('user-ticket-load');
        // User Support Tickets Ends

        Route::get('/affilate/program', [User\UserController::class, 'affilate_code'])->name('user-affilate-program');

        Route::get('/affilate/withdraw', [User\WithdrawController::class, 'index'])->name('user-wwt-index');
        Route::get('/affilate/withdraw/create', [User\WithdrawController::class, 'create'])->name('user-wwt-create');
        Route::post('/affilate/withdraw/create', [User\WithdrawController::class, 'store'])->name('user-wwt-store');

        // User Favorite Seller

        Route::get('/favorite/seller', [User\UserController::class, 'favorites'])->name('user-favorites');
        Route::get('/favorite/{id1}/{id2}', [User\UserController::class, 'favorite'])->name('user-favorite');
        Route::get('/favorite/seller/{id}/delete', [User\UserController::class, 'favdelete'])->name('user-favorite-delete');

    });

    // ************************************ USER SECTION ENDS**********************************************

    // ************************************ COURIER SECTION **********************************************
    Route::prefix('courier')->group(function () {

        // COURIER AUTH SECTION
        Route::get('/login', [Courier\LoginController::class, 'showLoginForm'])->name('courier.login');
        Route::post('/login', [Auth\Courier\LoginController::class, 'login'])->name('courier.login.submit');
        Route::get('/success/{status}', [Courier\LoginController::class, 'status'])->name('courier.success');

        Route::get('/register', [Courier\RegisterController::class, 'showRegisterForm'])->name('courier.register');

        // Courier Register
        Route::post('/register', [Auth\Courier\RegisterController::class, 'register'])->name('courier-register-submit');
        Route::get('/register/verify/{token}', [Auth\Courier\RegisterController::class, 'token'])->name('courier-register-token');
        // Courier Register End

        //------------ COURIER FORGOT SECTION ------------
        Route::get('/forgot', [Auth\Courier\ForgotController::class, 'index'])->name('courier.forgot');
        Route::post('/forgot', [Auth\Courier\ForgotController::class, 'forgot'])->name('courier.forgot.submit');
        Route::get('/change-password/{token}', [Auth\Courier\ForgotController::class, 'showChangePassForm'])->name('courier.change.token');
        Route::post('/change-password', [Auth\Courier\ForgotController::class, 'changepass'])->name('courier.change.password');

        //------------ COURIER FORGOT SECTION ENDS ------------

        Route::get('/logout', [Courier\LoginController::class, 'logout'])->name('courier-logout');
        Route::get('/dashboard', [Courier\CourierController::class, 'index'])->name('courier-dashboard');

        Route::get('/profile', [Courier\CourierController::class, 'profile'])->name('courier-profile');
        Route::post('/profile', [Courier\CourierController::class, 'profileupdate'])->name('courier-profile-update');

        Route::get('/service/area', [Courier\CourierController::class, 'serviceArea'])->name('courier-service-area');
        Route::get('/service/area/create', [Courier\CourierController::class, 'serviceAreaCreate'])->name('courier-service-area-create');
        Route::post('/service/area/create', [Courier\CourierController::class, 'serviceAreaStore'])->name('courier-service-area-store');
        Route::get('/service/area/edit/{id}', [Courier\CourierController::class, 'serviceAreaEdit'])->name('courier-service-area-edit');
        Route::post('/service/area/edit/{id}', [Courier\CourierController::class, 'serviceAreaUpdate'])->name('courier-service-area-update');
        Route::get('/service/area/delete/{id}', [Courier\CourierController::class, 'serviceAreaDestroy'])->name('courier-service-area-delete');
        Route::get('/service/area/toggle-status/{id}', [Courier\CourierController::class, 'serviceAreaToggleStatus'])->name('courier-service-area-toggle-status');
        Route::get('/service/area/cities', [Courier\CourierController::class, 'getCitiesByCountry'])->name('courier-get-cities');

        Route::get('/withdraw', [Courier\WithdrawController::class, 'index'])->name('courier-wwt-index');
        Route::get('/withdraw/create', [Courier\WithdrawController::class, 'create'])->name('courier-wwt-create');
        Route::post('/withdraw/create', [Courier\WithdrawController::class, 'store'])->name('courier-wwt-store');

        Route::get('my/purchases', [Courier\CourierController::class, 'orders'])->name('courier-purchases');
        Route::get('purchase/details/{id}', [Courier\CourierController::class, 'orderDetails'])->name('courier-purchase-details');
        Route::get('purchase/delivery/accept/{id}', [Courier\CourierController::class, 'orderAccept'])->name('courier-purchase-delivery-accept');
        Route::get('purchase/delivery/reject/{id}', [Courier\CourierController::class, 'orderReject'])->name('courier-purchase-delivery-reject');
        Route::get('purchase/delivery/complete/{id}', [Courier\CourierController::class, 'orderComplete'])->name('courier-purchase-delivery-complete');

        Route::get('/reset', [Courier\CourierController::class, 'resetform'])->name('courier-reset');
        Route::post('/reset', [Courier\CourierController::class, 'reset'])->name('courier-reset-submit');

        // Financial & Accounting Routes
        Route::get('/transactions', [Courier\CourierController::class, 'transactions'])->name('courier-transactions');
        Route::get('/settlements', [Courier\CourierController::class, 'settlements'])->name('courier-settlements');
        Route::get('/financial-report', [Courier\CourierController::class, 'financialReport'])->name('courier-financial-report');
    });

    // ************************************ COURIER SECTION ENDS**********************************************

    // ************************************ FRONT SECTION **********************************************


    Route::post('/item/report', [Front\CatalogController::class, 'report'])->name('catalog-item.report');

    Route::get('/', [Front\FrontendController::class, 'index'])->name('front.index');
    // Route removed - extraIndex merged into index() with section-based rendering

    // ALL CATALOGS PAGE (with pagination)
    Route::get('/catalogs', [Front\FrontendController::class, 'allCatalogs'])->name('front.catalogs');

    Route::get('/monetary-unit/{id}', [Front\FrontendController::class, 'monetaryUnit'])->name('front.monetary-unit');
    Route::get('/language/{id}', [Front\FrontendController::class, 'language'])->name('front.language');
    Route::get('/purchase/track/{id}', [Front\FrontendController::class, 'trackload'])->name('front.track.search');

    // SHIPMENT TRACKING SECTION
    // SHIPMENT TRACKING (NEW SYSTEM) - Public tracking page
    Route::get('/tracking', [User\ShipmentTrackingController::class, 'track'])->name('front.tracking');
    Route::get('/tracking/status', [User\ShipmentTrackingController::class, 'getStatus'])->name('front.tracking.status');

    // User shipment tracking (requires auth)
    Route::middleware('auth')->group(function() {
        Route::get('/my-shipments', [User\ShipmentTrackingController::class, 'index'])->name('user.shipment-tracking.index');
        Route::get('/my-shipments/{purchaseId}', [User\ShipmentTrackingController::class, 'show'])->name('user.shipment-tracking.show');
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
    Route::get('/contact', [Front\FrontendController::class, 'contact'])->name('front.contact');
    Route::post('/contact', [Front\FrontendController::class, 'contactemail'])->name('front.contact.submit');
    Route::get('/contact/refresh_code', [Front\FrontendController::class, 'refresh_code']);
    // CONTACT SECTION  ENDS

 
    // CATALOG ITEM AUTO SEARCH SECTION
    Route::get('/autosearch/catalog-item/{slug}', [Front\FrontendController::class, 'autosearch']);
    // CATALOG ITEM AUTO SEARCH SECTION ENDS

    // CATEGORY SECTION
    Route::get('/categories', [Front\CatalogController::class, 'categories'])->name('front.categories');

    // NEW: Unified catalog tree with recursive category traversal
    // Shows all items from selected category AND all descendants
    // UNIFIED: 5-level category route
    // Structure: /brands/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}
    // - brand = Brand slug (e.g., "nissan")
    // - catalog = Catalog slug (e.g., "safari-patrol-1997")
    // - cat1/cat2/cat3 = Category slugs (levels 1, 2, 3)
    Route::get('/brands/{brand?}/{catalog?}/{cat1?}/{cat2?}/{cat3?}', [Front\CatalogController::class, 'catalog'])->name('front.catalog');

    // AJAX APIs for catalog selector (lightweight on-demand loading)
    Route::get('/api/catalog/catalogs', [Front\CatalogController::class, 'getCatalogs'])->name('front.api.catalogs');
    Route::get('/api/catalog/tree', [Front\CatalogController::class, 'getTreeCategories'])->name('front.api.tree');

    // AJAX API for merchant branches (branches are fetched per merchant context only)
    Route::get('/api/merchant/branches', [Front\CatalogController::class, 'getMerchantBranches'])->name('front.api.merchant.branches');
    // CATALOG SECTION ENDS

    // COMPARE SECTION REMOVED - Feature deleted

    // SEARCH RESULTS PAGE - Shows catalog items matching search query
    // Displays cards with offers button and alternatives (like tree view)
    Route::get('/search', [Front\SearchResultsController::class, 'index'])->name('front.search-results');

    // PART RESULT PAGE - Shows all offers for a part number
    // NEW: CatalogItem-first approach (one page per part_number, not per merchant_item)
    Route::get('/result/{part_number}', [Front\PartResultController::class, 'show'])->name('front.part-result');

    // ============ NEW MERCHANT CART SYSTEM (v4) ============
    // Clean, unified cart API - replaces all old cart routes
    // Uses: App\Http\Controllers\Front\MerchantCartController
    // Service: App\Domain\Commerce\Services\Cart\MerchantCartManager
    // ALL operations are Branch-Scoped (except add which infers branch from item)
    Route::prefix('merchant-cart')->name('merchant-cart.')->group(function () {
        // Cart page view (grouped by branch)
        Route::get('/', [Front\MerchantCartController::class, 'index'])->name('index');

        // Get all branches cart (AJAX for full page)
        Route::get('/all', [Front\MerchantCartController::class, 'all'])->name('all');

        // Get branch cart summary (AJAX) - requires branch_id
        Route::get('/summary', [Front\MerchantCartController::class, 'summary'])->name('summary');

        // Cart count (for header badge)
        Route::get('/count', [Front\MerchantCartController::class, 'count'])->name('count');

        // Get branch IDs in cart
        Route::get('/branches', [Front\MerchantCartController::class, 'branches'])->name('branches');

        // Get merchant IDs in cart (legacy support)
        Route::get('/merchants', [Front\MerchantCartController::class, 'merchants'])->name('merchants');

        // Add item to cart (branch inferred from merchant_item_id)
        Route::post('/add', [Front\MerchantCartController::class, 'add'])->name('add');

        // Update item quantity - requires branch_id
        Route::post('/update', [Front\MerchantCartController::class, 'update'])->name('update');

        // Increase/Decrease quantity - requires branch_id
        Route::post('/increase', [Front\MerchantCartController::class, 'increase'])->name('increase');
        Route::post('/decrease', [Front\MerchantCartController::class, 'decrease'])->name('decrease');

        // Remove item - requires branch_id
        Route::delete('/remove/{key}', [Front\MerchantCartController::class, 'remove'])->name('remove');
        Route::post('/remove', [Front\MerchantCartController::class, 'remove'])->name('remove.post');

        // Clear branch items - requires branch_id
        Route::post('/clear-branch', [Front\MerchantCartController::class, 'clearBranch'])->name('clear-branch');

        // Clear all cart
        Route::post('/clear', [Front\MerchantCartController::class, 'clear'])->name('clear');
    });
    // ============ END NEW MERCHANT CART SYSTEM ============

    // FAVORITE SECTION
    Route::middleware('auth')->group(function () {
        Route::get('/favorite/add/merchant/{merchantItemId}', [User\FavoriteController::class, 'addMerchantFavorite'])->name('merchant.favorite.add');
        Route::get('/favorite/remove/merchant/{merchantItemId}', [User\FavoriteController::class, 'removeMerchantFavorite'])->name('merchant.favorite.remove');
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

    Route::post('/merchant/contact', [Front\MerchantController::class, 'merchantcontact'])->name('front.merchant.contact');

    // MERCHANT SECTION ENDS

    // SUBSCRIBE SECTION

    Route::post('/subscriber/store', [Front\FrontendController::class, 'subscribe'])->name('front.subscribe');

    // SUBSCRIBE SECTION ENDS

    // LOGIN WITH FACEBOOK OR GOOGLE SECTION
    Route::get('auth/{provider}', [Auth\User\SocialRegisterController::class, 'redirectToProvider'])->name('social-provider');
    Route::get('auth/{provider}/callback', [Auth\User\SocialRegisterController::class, 'handleProviderCallback']);
    // LOGIN WITH FACEBOOK OR GOOGLE SECTION ENDS

    //  CRONJOB

    Route::get('/merchant/subscription/check', [Front\FrontendController::class, 'subcheck']);

    // CRONJOB ENDS

    Route::post('the/muaadh/ocean/2441139', [Front\FrontendController::class, 'subscription']);
    Route::get('finalize', [Front\FrontendController::class, 'finalize']);
    Route::get('update-finalize', [Front\FrontendController::class, 'updateFinalize']);

    // MERCHANT AND PAGE SECTION
    Route::get('/{slug}', [Front\MerchantController::class, 'index'])->name('front.merchant');

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

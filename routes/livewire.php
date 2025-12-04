<?php

use App\Http\Controllers\Front\VehicleCatalogController;
use App\Livewire\CalloutModal;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Catalog Routes (Controller-based - No Livewire)
|--------------------------------------------------------------------------
|
| These routes were previously Livewire components but have been converted
| to standard controllers for better performance and maintainability.
|
*/

Route::prefix('catlogs')
    ->middleware(['web', 'localization'])
    ->group(function () {
        // Brand catalogs list - catalog.index (also aliased as catlogs.index)
        Route::get('{brand}', [VehicleCatalogController::class, 'index'])
            ->name('catalog.index');

        // Level 1 categories - catalog.level1 (also aliased as tree.level1)
        Route::get('{brand}/{catalog}', [VehicleCatalogController::class, 'level1'])
            ->name('catalog.level1');

        // Level 2 subcategories - catalog.level2 (also aliased as tree.level2)
        Route::get('{brand}/{catalog}/{key1}', [VehicleCatalogController::class, 'level2'])
            ->name('catalog.level2');

        // Level 3 parts - catalog.level3 (also aliased as tree.level3)
        Route::get('{brand}/{catalog}/{key1}/{key2}', [VehicleCatalogController::class, 'level3'])
            ->name('catalog.level3');

        // Illustrations page
        Route::get('{brand}/{catalog}/{key1}/{key2}/{key3}', [VehicleCatalogController::class, 'illustrations'])
            ->name('catalog.illustrations');
    });

// Callout Modal (still using Livewire)
Route::get('/callout-modal', CalloutModal::class);

// Search Results (Controller-based)
Route::get('result/{sku}', [\App\Http\Controllers\Front\SearchResultsController::class, 'show'])->name('search.result');

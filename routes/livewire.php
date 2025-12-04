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
| Route names are kept the same for backwards compatibility:
| - catlogs.index
| - tree.level1
| - tree.level2
| - tree.level3
| - illustrations
|
*/

Route::prefix('catlogs')
    ->middleware(['web', 'localization'])
    ->group(function () {
        // Brand catalogs list
        Route::get('{brand}', [VehicleCatalogController::class, 'index'])
            ->name('catlogs.index');

        // Level 1 categories
        Route::get('{brand}/{catalog}', [VehicleCatalogController::class, 'level1'])
            ->name('tree.level1');

        // Level 2 subcategories
        Route::get('{brand}/{catalog}/{key1}', [VehicleCatalogController::class, 'level2'])
            ->name('tree.level2');

        // Level 3 parts
        Route::get('{brand}/{catalog}/{key1}/{key2}', [VehicleCatalogController::class, 'level3'])
            ->name('tree.level3');

        // Illustrations page
        Route::get('{brand}/{catalog}/{key1}/{key2}/{key3}', [VehicleCatalogController::class, 'illustrations'])
            ->name('illustrations');
    });

// Callout Modal (still using Livewire)
Route::get('/callout-modal', CalloutModal::class);

// Search Results (Controller-based)
Route::get('result/{sku}', [\App\Http\Controllers\Front\SearchResultsController::class, 'show'])->name('search.result');

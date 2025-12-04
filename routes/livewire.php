<?php

use App\Livewire\Catlogs;
use App\Livewire\CatlogsProducts;
use App\Livewire\CatlogTree;
use App\Livewire\CatlogTreeLevel1;
use App\Livewire\CatlogTreeLevel2;
use App\Livewire\CatlogTreeLevel3;
use App\Livewire\CalloutModal; // ✅ استدعاء مكون CalloutViewer
use Illuminate\Support\Facades\Route;

Route::prefix('catlogs')
->middleware(['web','localization'])
->group(function () {
    Route::get('{id}', Catlogs::class)->name('catlogs.index');
    Route::get('{id}/{data}', CatlogTreeLevel1::class)->name('tree.level1');
    Route::get('{id}/{data}/{key1}', CatlogTreeLevel2::class)->name('tree.level2');
    Route::get('{id}/{data}/{key1}/{key2}', CatlogTreeLevel3::class)->name('tree.level3');
    Route::get('{id}/{data}/{key1}/{key2}/{key3}', \App\Livewire\Illustrations::class)->name('illustrations');
});

Route::get('/callout-modal', CalloutModal::class);

// Route::get('/CalloutModal/{data}/{code}/{callout}', CalloutModal::class)->name('CalloutModal'); // ✅ هذا هو المطلوب

// Changed from Livewire to Controller to support AJAX includes
Route::get('result/{sku}', [\App\Http\Controllers\Front\SearchResultsController::class, 'show'])->name('search.result');

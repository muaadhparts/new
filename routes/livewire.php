<?php


use App\Livewire\Catlogs;
use App\Livewire\CatlogsProducts;
use App\Livewire\CatlogTree;
use App\Livewire\CatlogTreeLevel1;
use App\Livewire\CatlogTreeLevel2;
use App\Livewire\CatlogTreeLevel3;
use App\Livewire\Level1Tree;
use Illuminate\Support\Facades\Route;


Route::prefix('catlogs')->group(function () {
    Route::get('{id}', Catlogs::class)->name('catlogs.index');
    Route::get('{id}/{data}', CatlogTreeLevel1::class)->name('tree.level1');

    Route::get('{id}/{data}/{key1}', CatlogTreeLevel2::class)->name('tree.level2');
    Route::get('{id}/{data}/{key1}/{key2}', CatlogTreeLevel3::class)->name('tree.level3');
    Route::get('{id}/{data}/{key1}/{key2}/{code}', \App\Livewire\Illustrations::class)->name('illustrations');

//    Route::get('{id}/{data}/{code}/{key2}', Level1Tree::class)->name('child2.tree');
//    Route::get('{id}/{data}/{code}/{key2}/', Child3CatlogTree::class)->name('child3.tree');
});

Route::get( '{id}/{data}/products/{products}', CatlogsProducts::class)->name('catlogs.products');

Route::get('result/{sku}', \App\Livewire\SearchResultsPage::class)->name('search.result');

//
//
//Route::get('catlogs/{id}' ,Catlogs::class)->name('catlogs.index');
//Route::get('catlogs/{id}/{data}' ,CatlogTree::class)->name('catlogs.tree');
//Route::get('catlogs/{id}/{data}/{code}' ,ChildCatlogTree::class)->name('child.tree');
//Route::get('catlogs/{id}/{data}/{code}/{key2}' ,ChildCatlogTree::class)->name('child2.tree');
//Route::get('catlogs/{id}/{data}/{code}/{key2}' ,ChildCatlogTree::class)->name('child2.tree');
////Route::get('ca/{id}' ,Catlogs::class)->name('catlogs.index');

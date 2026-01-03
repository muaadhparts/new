{{--
================================================================================
SECTION PARTIAL: CatalogItem Grid
================================================================================
Receives: $catalogItems (collection of CatalogItem models with merchantItems loaded)
All catalogItems are merchant-based (is_merchant = 2)
================================================================================
--}}

<div class="row">
    @foreach($catalogItems as $catalogItem)
    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
        @include('partials.catalogItem', ['catalogItem' => $catalogItem])
    </div>
    @endforeach
</div>

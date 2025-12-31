{{--
================================================================================
SECTION PARTIAL: Product Grid
================================================================================
Receives: $products (collection of CatalogItem models with merchantItems loaded)
All products are merchant-based (is_merchant = 2)
================================================================================
--}}

<div class="row">
    @foreach($products as $product)
    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
        @include('partials.product', ['product' => $product])
    </div>
    @endforeach
</div>

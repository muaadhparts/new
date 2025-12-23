{{--
================================================================================
SECTION PARTIAL: Product Grid
================================================================================
Receives: $products (collection of Product models with merchantProducts loaded)
All products are vendor-based (is_vendor = 2)
================================================================================
--}}

<div class="row">
    @foreach($products as $product)
    <div class="col-lg-3 col-md-4 col-sm-6 col-12 mb-4">
        @include('partials.product', ['product' => $product])
    </div>
    @endforeach
</div>

{{--
================================================================================
SECTION PARTIAL: Product Slider
================================================================================
Receives: $merchantProducts (collection of MerchantProduct models)
Each MerchantProduct = one listing (product + vendor + quality brand)
Uses the same slider style as Related Products
================================================================================
--}}

<div class="gs-product-cards-slider-area">
    <div class="product-cards-slider">
        @foreach($merchantProducts as $mp)
            @include('includes.frontend.home_product', [
                'class' => '',
                'product' => $mp->product,
                'mp' => $mp
            ])
        @endforeach
    </div>
</div>

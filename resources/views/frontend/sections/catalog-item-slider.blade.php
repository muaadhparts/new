{{--
================================================================================
SECTION PARTIAL: Product Slider
================================================================================
Receives: $merchantItems (collection of MerchantItem models)
Each MerchantItem = one listing (catalogItem + vendor + quality brand)
Uses the same slider style as Related Products
================================================================================
--}}

<div class="gs-product-cards-slider-area">
    <div class="product-cards-slider">
        @foreach($merchantItems as $mp)
            @include('includes.frontend.home_catalog_item', [
                'class' => '',
                'catalogItem' => $mp->catalogItem,
                'mp' => $mp
            ])
        @endforeach
    </div>
</div>
